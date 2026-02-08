from __future__ import annotations

import os
from dataclasses import dataclass
from datetime import datetime, date
from typing import Any, Dict, List, Optional, Tuple

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder
from sklearn.linear_model import Ridge


# ------------------------------------------------------------
# Config
# ------------------------------------------------------------
DATASET_PATH = os.getenv(
    "DATASET_PATH",
    os.path.join(os.path.dirname(__file__), "data", "agri_ai_dataset_2024_2026.csv"),
)

# Training cut-off (user requirement): use history until 2026-01-10 inclusive
TRAIN_CUTOFF = date(2026, 1, 10)

# Keep predictions within a realistic demo range (JOD/kg)
MIN_PRICE = float(os.getenv("AI_MIN_PRICE", "0.15"))
MAX_PRICE = float(os.getenv("AI_MAX_PRICE", "0.70"))

app = Flask(__name__)
CORS(app)  # allow calls from your PHP website


# ------------------------------------------------------------
# Utilities
# ------------------------------------------------------------
def _safe_float(x: Any) -> Optional[float]:
    try:
        if x is None or x == "":
            return None
        return float(x)
    except Exception:
        return None


def _safe_int(x: Any) -> Optional[int]:
    try:
        if x is None or x == "":
            return None
        return int(x)
    except Exception:
        return None


def _parse_date(x: Any) -> Optional[date]:
    if not x:
        return None
    try:
        if isinstance(x, str) and "T" in x:
            return datetime.fromisoformat(x.replace("Z", "+00:00")).date()
        return datetime.strptime(str(x), "%Y-%m-%d").date()
    except Exception:
        return None


def _month_clamp(m: int) -> int:
    return max(1, min(12, int(m)))


def _stable_seed(*parts: Any) -> int:
    s = "|".join(map(str, parts))
    return abs(hash(s)) % (2**31)


def _seasonal_index(month: int) -> float:
    """Smooth seasonality factor by month (explainable, stable)."""
    m = _month_clamp(month)
    return float(1.0 + 0.08 * np.sin((m - 1) / 12.0 * 2 * np.pi))


def _clip_price(x: float) -> float:
    return float(np.clip(float(x), MIN_PRICE, MAX_PRICE))


# ------------------------------------------------------------
# ML Core
# ------------------------------------------------------------
@dataclass
class AISystem:
    df_monthly: pd.DataFrame
    df_daily: pd.DataFrame
    model_daily: Pipeline
    products: List[str]
    markets: List[str]
    years: List[int]
    min_date: date
    max_date: date


def load_monthly_dataset(path: str) -> pd.DataFrame:
    if not os.path.exists(path):
        raise FileNotFoundError(f"Dataset not found: {path}")

    df = pd.read_csv(path)

    required = {"year", "month", "product", "market", "price_jod", "quantity_kg"}
    missing = required - set(df.columns)
    if missing:
        raise ValueError(f"Dataset missing columns: {missing}")

    df["year"] = df["year"].astype(int)
    df["month"] = df["month"].astype(int).clip(1, 12)
    df["product"] = df["product"].astype(str).str.strip()
    df["market"] = df["market"].astype(str).str.strip()
    df["price_jod"] = df["price_jod"].astype(float)
    df["quantity_kg"] = df["quantity_kg"].astype(int)

    return df


def _monthly_lookup(df_monthly: pd.DataFrame) -> Dict[Tuple[int, int, str, str], Tuple[float, int]]:
    """(year, month, product, market) -> (price, quantity)"""
    mp: Dict[Tuple[int, int, str, str], Tuple[float, int]] = {}
    for r in df_monthly.itertuples(index=False):
        mp[(int(r.year), int(r.month), str(r.product), str(r.market))] = (float(r.price_jod), int(r.quantity_kg))
    return mp


def build_daily_dataset(df_monthly: pd.DataFrame, cutoff: date = TRAIN_CUTOFF) -> pd.DataFrame:
    """
    Expand monthly dataset into a deterministic daily dataset (for daily forecasting UI).

    Why: the provided dataset is monthly, while the project UI and demo require daily predictions.
    We generate stable daily variations using:
      - monthly base price
      - seasonality by month
      - weekday pattern
      - small deterministic noise (seeded by product/market/date)
    """
    mp = _monthly_lookup(df_monthly)

    start = date(2024, 1, 1)
    end = cutoff
    days = pd.date_range(start=start, end=end, freq="D")

    rows: List[Dict[str, Any]] = []
    # iterate per day and per product/market combo found in dataset (fast enough for ~18k rows)
    combos = sorted({(str(p), str(m)) for p, m in zip(df_monthly["product"], df_monthly["market"])})
    for dt in days:
        d: date = dt.date()
        y, mo = d.year, d.month
        dow = d.weekday()
        doy = int(d.strftime("%j"))
        for product, market in combos:
            base_price, qty = mp.get((y, mo, product, market), (None, None))
            if base_price is None:
                # fallback: use same month/year average for product across markets
                sub = df_monthly[(df_monthly["year"] == y) & (df_monthly["month"] == mo) & (df_monthly["product"] == product)]
                if len(sub) == 0:
                    continue
                base_price = float(sub["price_jod"].mean())
                qty = int(sub["quantity_kg"].median())

            seed = _stable_seed(product, market, d.isoformat())
            rng = np.random.default_rng(seed)

            wobble = float(rng.normal(0, 0.015))  # ~1.5% noise
            weekday_adj = (dow - 3) * 0.002  # small mid-week lift
            price = float(base_price) * _seasonal_index(mo) * (1 + wobble + weekday_adj)
            price = _clip_price(price)

            rows.append(
                {
                    "date": pd.to_datetime(d),
                    "year": int(y),
                    "month": int(mo),
                    "day": int(d.day),
                    "dayofweek": int(dow),
                    "dayofyear": int(doy),
                    "product": product,
                    "market": market,
                    "quantity_kg": int(qty),
                    "price_jod": float(round(price, 4)),
                }
            )

    return pd.DataFrame(rows)


def train_daily_model(df_daily: pd.DataFrame) -> Pipeline:
    X = df_daily[["year", "month", "day", "dayofweek", "dayofyear", "product", "market", "quantity_kg"]]
    y = df_daily["price_jod"]

    preprocessor = ColumnTransformer(
        transformers=[
            ("cat", OneHotEncoder(handle_unknown="ignore"), ["product", "market"]),
            ("num", "passthrough", ["year", "month", "day", "dayofweek", "dayofyear", "quantity_kg"]),
        ]
    )

    # Ridge keeps it stable for extrapolation (future dates beyond cutoff)
    model = Pipeline(steps=[("prep", preprocessor), ("reg", Ridge(alpha=1.0))])
    model.fit(X, y)
    return model


def boot_ai() -> AISystem:
    df_monthly = load_monthly_dataset(DATASET_PATH)

    # Build + train daily model using history until cutoff
    df_daily = build_daily_dataset(df_monthly, cutoff=TRAIN_CUTOFF)
    model_daily = train_daily_model(df_daily)

    products = sorted(df_monthly["product"].unique().tolist())
    markets = sorted(df_monthly["market"].unique().tolist())
    years = sorted(df_monthly["year"].unique().tolist())

    min_date = df_daily["date"].min().date()
    max_date = df_daily["date"].max().date()
    return AISystem(
        df_monthly=df_monthly,
        df_daily=df_daily,
        model_daily=model_daily,
        products=products,
        markets=markets,
        years=years,
        min_date=min_date,
        max_date=max_date,
    )


AI = boot_ai()


# ------------------------------------------------------------
# Predictions
# ------------------------------------------------------------
def predict_price_by_date(d: date, product: str, market: str, quantity_kg: int) -> float:
    product = (product or "").strip()
    market = (market or "").strip()
    if product not in AI.products:
        product = AI.products[0] if AI.products else product
    if market not in AI.markets:
        market = AI.markets[0] if AI.markets else market

    X = pd.DataFrame(
        [
            {
                "year": int(d.year),
                "month": int(d.month),
                "day": int(d.day),
                "dayofweek": int(d.weekday()),
                "dayofyear": int(d.strftime("%j")),
                "product": str(product),
                "market": str(market),
                "quantity_kg": int(max(1, quantity_kg)),
            }
        ]
    )
    pred = float(AI.model_daily.predict(X)[0])
    pred = _clip_price(pred)
    return round(pred, 2)


def predict_price_monthly(year: int, month: int, product: str, market: str, quantity_kg: int) -> float:
    """Backward-compatible monthly endpoint: use the 15th day of the month as representative."""
    d = date(int(year), _month_clamp(int(month)), 15)
    return predict_price_by_date(d, product, market, quantity_kg)


# ------------------------------------------------------------
# Market recommendation (ML)
# ------------------------------------------------------------
def recommend_market(year: int, month: int, product: str, quantity_kg: int) -> Dict[str, Any]:
    product = (product or "").strip()
    if product not in AI.products:
        product = AI.products[0] if AI.products else product

    # representative date inside that month
    d = date(int(year), _month_clamp(int(month)), 15)

    preds = []
    for mkt in AI.markets:
        p = predict_price_by_date(d, product, mkt, quantity_kg)
        preds.append({"market": mkt, "predicted_price_jod": p})

    best_sell = max(preds, key=lambda x: x["predicted_price_jod"])
    best_buy = min(preds, key=lambda x: x["predicted_price_jod"])

    # historical mean for this month + product (all markets) from monthly dataset
    hist = AI.df_monthly[(AI.df_monthly["product"] == product) & (AI.df_monthly["month"] == _month_clamp(month))]
    hist_mean = round(float(hist["price_jod"].mean()), 2) if len(hist) else None

    return {
        "year": int(year),
        "month": _month_clamp(month),
        "product": product,
        "quantity_kg": int(quantity_kg),
        "best_market_to_sell": best_sell,
        "best_market_to_buy": best_buy,
        "all_markets": sorted(preds, key=lambda x: x["predicted_price_jod"], reverse=True),
        "historical_mean_price_jod_for_month": hist_mean,
        "model": "Daily Ridge Regression + OneHotEncoder (product, market)",
        "train_cutoff": TRAIN_CUTOFF.isoformat(),
        "dataset_rows_monthly": int(len(AI.df_monthly)),
        "dataset_rows_daily": int(len(AI.df_daily)),
    }


# ------------------------------------------------------------
# Farmer: listing price suggestion (daily)
# ------------------------------------------------------------
def _daily_series(product: str, market: str, d: date, quantity_kg: int, days_back: int = 30) -> pd.DataFrame:
    rows = []
    for i in range(days_back, -1, -1):
        di = (pd.Timestamp(d) - pd.Timedelta(days=i)).date()
        p = predict_price_by_date(di, product, market, quantity_kg)
        rows.append({"date": pd.to_datetime(di), "price": float(p)})
    df = pd.DataFrame(rows).sort_values("date")
    return df


def suggest_listing_price(product: str, market: str, d: date, quantity_kg: int, current_price: Optional[float]) -> Dict[str, Any]:
    product = (product or "").strip() or (AI.products[0] if AI.products else "Tomato")
    market = (market or "").strip() or (AI.markets[0] if AI.markets else "Amman")
    if product not in AI.products:
        product = AI.products[0]
    if market not in AI.markets:
        market = AI.markets[0]

    hist = _daily_series(product, market, d, quantity_kg, days_back=30)
    hist["ma7"] = hist["price"].rolling(7).mean()

    today_row = hist.iloc[-1]
    yest_row = hist.iloc[-2] if len(hist) >= 2 else today_row

    expected_today = float(today_row["ma7"]) if not np.isnan(today_row["ma7"]) else float(today_row["price"])
    expected_yesterday = float(yest_row["ma7"]) if not np.isnan(yest_row["ma7"]) else float(yest_row["price"])
    expected_today = _clip_price(expected_today)
    expected_yesterday = _clip_price(expected_yesterday)

    pct = 0.0
    if expected_yesterday > 0:
        pct = (expected_today - expected_yesterday) / expected_yesterday * 100

    trend = "flat"
    if pct > 0.6:
        trend = "up"
    elif pct < -0.6:
        trend = "down"

    # Next month expectation (same market) for a simple "wait" advice
    ny, nm = d.year, d.month + 1
    if nm > 12:
        nm = 1
        ny += 1
    next_month_price = predict_price_monthly(int(ny), int(nm), product, market, quantity_kg)

    advice = "sell_now"
    if next_month_price >= expected_today * 1.05:
        advice = "wait_month"

    suggested = expected_today * 1.03  # small margin
    suggested = _clip_price(suggested)

    badge = False
    if current_price is not None and current_price > 0:
        badge = suggested >= (float(current_price) * 1.05)

    return {
        "ok": True,
        "date": d.isoformat(),
        "product": product,
        "market": market,
        "quantity_kg": int(quantity_kg),
        "expected_price_today": round(float(expected_today), 2),
        "expected_price_yesterday": round(float(expected_yesterday), 2),
        "trend": trend,
        "pct_change": round(float(pct), 2),
        "suggested_listing_price": round(float(suggested), 2),
        "next_month_expected": round(float(next_month_price), 2),
        "advice": advice,  # sell_now | wait_month
        "badge_ai_recommended": bool(badge),
        "explain": {
            "method": "Daily model (Ridge) + MA(7) trend",
            "train_cutoff": TRAIN_CUTOFF.isoformat(),
            "price_range_jod": [MIN_PRICE, MAX_PRICE],
        },
    }


# ------------------------------------------------------------
# Legacy role tips (kept for transporter/store pages)
# ------------------------------------------------------------
def recommend_for_transporter(payload: Dict[str, Any]) -> Dict[str, Any]:
    vehicle = (payload.get("vehicle_type") or "vehicle").strip()
    location = (payload.get("location") or "your area").strip()
    content = (
        f"Recommendation: Prioritize requests near {location} and batch deliveries using your {vehicle}. "
        "Start with pending requests, then update status to in_progress and delivered to build trust (ratings)."
    )
    return {"recommendation_type": "route_optimization", "vehicle_type": vehicle, "content": content}


def recommend_for_store(payload: Dict[str, Any]) -> Dict[str, Any]:
    season = (payload.get("season") or "current season").strip()
    content = (
        f"Recommendation: Increase orders for high-demand produce in the {season}. "
        "Use ratings and delivered history to choose reliable farmers/transporters."
    )
    return {"recommendation_type": "demand_forecast", "content": content}


# ------------------------------------------------------------
# Routes
# ------------------------------------------------------------
@app.get("/health")
def health():
    return jsonify(
        {
            "ok": True,
            "service": "ai_service",
            "dataset": os.path.basename(DATASET_PATH),
            "rows_monthly": int(len(AI.df_monthly)),
            "rows_daily": int(len(AI.df_daily)),
            "train_cutoff": TRAIN_CUTOFF.isoformat(),
            "products": AI.products,
            "markets": AI.markets,
            "min_date": AI.min_date.isoformat(),
            "max_date": AI.max_date.isoformat(),
            "ts": datetime.utcnow().isoformat(),
        }
    )


@app.get("/meta")
def meta():
    return jsonify(
        {
            "products": AI.products,
            "markets": AI.markets,
            "years": sorted(list(set([int(y) for y in AI.years] + [2026]))),  # keep 2026 visible in UI
            "months": list(range(1, 13)),
            "train_cutoff": TRAIN_CUTOFF.isoformat(),
            "min_date": AI.min_date.isoformat(),
            "max_date": AI.max_date.isoformat(),
            "price_range_jod": [MIN_PRICE, MAX_PRICE],
        }
    )


@app.post("/predict")
def api_predict_monthly():
    """Backward-compatible monthly prediction."""
    data = request.get_json(force=True, silent=True) or {}
    year = int(data.get("year", 2026))
    month = _month_clamp(int(data.get("month", 1)))
    product = str(data.get("product", AI.products[0] if AI.products else "Tomato"))
    market = str(data.get("market", AI.markets[0] if AI.markets else "Amman"))
    quantity = int(data.get("quantity_kg", 500))

    price = predict_price_monthly(year, month, product, market, quantity)
    return jsonify(
        {
            "year": year,
            "month": month,
            "product": product,
            "market": market,
            "quantity_kg": quantity,
            "predicted_price_jod": price,
            "mode": "monthly",
            "train_cutoff": TRAIN_CUTOFF.isoformat(),
        }
    )


@app.post("/predict_daily")
def api_predict_daily():
    """
    Daily prediction endpoint (recommended for demo days like 2026-01-26).
    Input JSON:
      { product, market, date: 'YYYY-MM-DD', quantity_kg }
    """
    data = request.get_json(force=True, silent=True) or {}
    product = str(data.get("product", AI.products[0] if AI.products else "Tomato"))
    market = str(data.get("market", AI.markets[0] if AI.markets else "Amman"))
    quantity = int(data.get("quantity_kg", 500))
    d = _parse_date(data.get("date")) or date.today()

    price = predict_price_by_date(d, product, market, quantity)
    return jsonify(
        {
            "date": d.isoformat(),
            "product": product,
            "market": market,
            "quantity_kg": quantity,
            "predicted_price_jod": price,
            "mode": "daily",
            "train_cutoff": TRAIN_CUTOFF.isoformat(),
        }
    )


@app.post("/recommend")
def api_recommend():
    data = request.get_json(force=True, silent=True) or {}
    year = int(data.get("year", 2026))
    month = _month_clamp(int(data.get("month", 1)))
    product = str(data.get("product", AI.products[0] if AI.products else "Tomato"))
    quantity = int(data.get("quantity_kg", 500))
    return jsonify(recommend_market(year, month, product, quantity))


@app.post("/suggest_price")
def api_suggest_price():
    """Farmer endpoint: suggest a listing price based on daily history & seasonality."""
    data = request.get_json(force=True, silent=True) or {}
    product = str(data.get("product", ""))
    market = str(data.get("market", ""))
    quantity = int(data.get("quantity_kg", 500))
    current_price = _safe_float(data.get("current_price"))
    d = _parse_date(data.get("date")) or date.today()
    return jsonify(suggest_listing_price(product, market, d, max(1, quantity), current_price))


@app.post("/recommend_role")
def api_recommend_role():
    """
    Backward-compatible endpoint:
      { role: farmer|transporter|store, payload: {...} }
    """
    data = request.get_json(force=True, silent=True) or {}
    role = (data.get("role") or "").strip().lower()
    payload = data.get("payload") or {}
    if not isinstance(payload, dict):
        payload = {}

    if role == "transporter":
        return jsonify({"ok": True, "role": "transporter", "data": recommend_for_transporter(payload)})
    if role == "store":
        return jsonify({"ok": True, "role": "store", "data": recommend_for_store(payload)})

    # farmer fallback: use recommend endpoint
    product = str(payload.get("product") or (AI.products[0] if AI.products else "Tomato"))
    year = int(payload.get("year") or datetime.now().year)
    month = _month_clamp(int(payload.get("month") or datetime.now().month))
    qty = int(payload.get("quantity_kg") or 500)
    return jsonify({"ok": True, "role": "farmer", "data": recommend_market(year, month, product, qty)})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.getenv("PORT", "5000")), debug=True)
