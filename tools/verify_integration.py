#!/usr/bin/env python3
"""Read-only integration preflight for the project's actual checkout. Python 3 stdlib."""
import argparse, json, re, subprocess, sys
from pathlib import Path

REQUIRED = [
 'includes/auth_session.php', 'api/v1/_bootstrap.php',
 'app/Services/ProductCatalog.php', 'api/v1/products/index.php',
 'store/place_order.php', 'store/available_products.php',
 'farmer/store_orders.php', 'farmer/delete_product.php',
 'transporter/accept_request.php','transporter/update_request_status.php',
 'admin/dashboard.php', 'database/migrations/005_products_soft_delete.sql',
 'database/migrations/006_order_snapshots_inventory_movements.sql',
]
CONTRACTS = {
 'store/place_order.php': ['csrf', 'product_name_at_order', 'unit_price_at_order'],
 'farmer/store_orders.php': ['inventory_movements', 'beginTransaction'],
 'farmer/delete_product.php': ['is_active'],
 'store/available_products.php': ['is_active'],
 'transporter/accept_request.php': ['POST', 'csrf'],
 'transporter/update_request_status.php': ['POST', 'csrf'],
 'admin/dashboard.php': ['csrf'],
}

def main():
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--root',default='.',help='Git checkout root')
    p.add_argument('--json',default=None,help='Optional report filename (avoid committing reports containing paths)')
    p.add_argument('--php',default='php',help='PHP CLI executable path')
    args=p.parse_args()
    root=Path(args.root).resolve()
    findings=[]
    def add(level,code,path,detail): findings.append(dict(level=level,code=code,path=path,detail=detail))
    for name in REQUIRED:
        if not (root/name).is_file(): add('FAIL','required_file_missing',name,'Expected file not found; check phases and ZIP extraction paths')
    for name, tokens in CONTRACTS.items():
        path=root/name
        if not path.is_file(): continue
        txt=path.read_text(encoding='utf-8-sig',errors='replace')
        for token in tokens:
            if token.lower() not in txt.lower(): add('REVIEW','contract_indicator_missing',name,f'Expected indicator not found: {token}. Inspect logic manually; this is heuristic.')
    for name in ['.env','config/.env','uploads/verification']:
        path=root/name
        if path.exists(): add('INFO','sensitive_local_path',name,'May exist locally; verify Git does not track it')
    git=subprocess.run(['git','ls-files','--cached','--','.env','config/.env','uploads/verification'],cwd=root,capture_output=True,text=True,check=False) if (root/'.git').exists() else None
    if git and git.returncode==0:
        for name in git.stdout.splitlines(): add('FAIL','tracked_sensitive_file',name,'Sensitive path is tracked in Git; removing it from latest commit does not erase history')
    if git is None: add('REVIEW','git_not_available','.git','Run on actual Git development checkout')
    if git and git.returncode!=0: add('REVIEW','git_check_failed','git',git.stderr.strip())
    # No database access is attempted. Do not print credentials or file content.
    php_files=[x for x in root.rglob('*.php') if '.git' not in x.parts and 'vendor' not in x.parts]
    try:
        test=subprocess.run([args.php,'-v'],capture_output=True,text=True,check=False)
        php_ok=test.returncode==0
    except FileNotFoundError: php_ok=False
    if not php_ok: add('REVIEW','php_cli_unavailable','php','PHP lint skipped; use --php C:\\path\\to\\php.exe')
    else:
        for f in php_files:
            result=subprocess.run([args.php,'-l',str(f)],capture_output=True,text=True,check=False)
            if result.returncode: add('FAIL','php_syntax_error',str(f.relative_to(root)),(result.stdout+' '+result.stderr).strip()[:300])
    counts={level:sum(x['level']==level for x in findings) for level in ('FAIL','REVIEW','INFO')}
    report={'project_root':str(root),'php_files_found':len(php_files),'php_lint_executed':php_ok,'counts':counts,'findings':findings,'notes':['Static heuristic only; cannot prove order workflow correctness or database migration state.','Run SQL preflight on staging and manually test all four roles.']}
    if args.json: Path(args.json).write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
    print(json.dumps(report,ensure_ascii=False,indent=2))
    return 1 if counts['FAIL'] else 0
if __name__=='__main__': sys.exit(main())
