#!/usr/bin/env python3
"""
Полная очистка (удаление контейнеров и данных)
"""

import subprocess
import os

os.chdir(os.path.dirname(os.path.abspath(__file__)))

print("[!] Полная очистка проекта...")
print()

subprocess.run("docker-compose down -v --remove-orphans", shell=True)
subprocess.run("docker rm -f doctors_db doctors_wordpress doctors_wpcli", shell=True)

# Удаление volumes
for prefix in ["testwork-main", "testworkmain", "wordpress-doctors", "wordpressdoctors", "testwork"]:
    subprocess.run(f"docker volume rm -f {prefix}_db_data", shell=True, capture_output=True)
    subprocess.run(f"docker volume rm -f {prefix}_wordpress_data", shell=True, capture_output=True)

print()
print("[+] Очистка завершена")

input("\nНажмите Enter для выхода...")