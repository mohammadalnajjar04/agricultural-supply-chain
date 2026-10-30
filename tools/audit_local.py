#!/usr/bin/env python3
"""Read-only static preflight for the CURRENT local Git checkout; stdlib only."""
import argparse, pathlib, subprocess, sys, json, re
p=argparse.ArgumentParser(description="Audit a PHP checkout without changing its files or database")
p.add_argument('--root',default='.',help='local project checkout root')
a=p.parse_args(); root=pathlib.Path(a.root).resolve()
if not (root/'farmer').is_dir() or not (root/'store').is_dir():
 print('ERROR: Run from the project root (farmer/ and store/ missing).');sys.exit(2)
issues=[]; checked=0
for f in sorted(root.rglob('*.php')):
 if any(x in f.parts for x in ('.git','vendor','node_modules')):continue
 checked+=1
 try:r=subprocess.run(['php','-l',str(f)],capture_output=True,text=True)
 except FileNotFoundError:print('ERROR: PHP CLI is not installed/in PATH');sys.exit(2)
 if r.returncode:issues.append({'type':'php_syntax','file':str(f.relative_to(root)),'detail':(r.stdout+r.stderr).strip()})
required=['includes/auth_session.php','includes/security.php','api/v1/_bootstrap.php','store/place_order.php','farmer/store_orders.php','transporter/accept_request.php','transporter/update_request_status.php']
for item in required:
 if not (root/item).is_file():issues.append({'type':'missing_required_file','file':item})
# dangerous secrets indicators, not reading .env (which should remain untracked)
for f in root.rglob('*.php'):
 if any(x in f.parts for x in ('.git','vendor','node_modules')):continue
 s=f.read_text(encoding='utf-8',errors='replace')
 if re.search(r'(?i)(?:password|db_pass|db_password)\s*=\s*[\'\"][^\'\"$]{4,}[\'\"]',s):
  issues.append({'type':'review_possible_hardcoded_password','file':str(f.relative_to(root))})
for sensitive in ['.env','uploads/verification']:
 if (root/sensitive).exists():
  issues.append({'type':'manual_check_git_tracking','path':sensitive,'detail':'Presence is not proof it is tracked: run git ls-files -- '+sensitive})
print(json.dumps({'project_root':str(root),'php_files_linted':checked,'issues':issues,'pass_static_preflight':not any(i['type'] in ('php_syntax','missing_required_file') for i in issues),'note':'Does not run SQL migrations, HTTP tests, CSRF tests, or MySQL transactions.'},indent=2))
sys.exit(1 if any(i['type'] in ('php_syntax','missing_required_file') for i in issues) else 0)
