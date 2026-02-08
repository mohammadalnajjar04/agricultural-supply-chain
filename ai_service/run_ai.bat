@echo off
REM Run the Python AI microservice (Windows)
cd /d %~dp0
python -m pip install -r requirements.txt
python app.py
