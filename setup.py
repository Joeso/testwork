#!/usr/bin/env python3
"""
Запуск Docker контейнеров для WordPress Doctors CPT
"""

import subprocess
import os
import sys

def main():
    project_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(project_dir)
    
    print()
    print("=" * 50)
    print("     Запуск Docker контейнеров")
    print("=" * 50)
    print()
    
    # Создаём docker-compose.yml
    print("[*] Создание docker-compose.yml...")
    
    content = '''services:
  db:
    image: mysql:5.7
    container_name: doctors_db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"

  wordpress:
    image: wordpress:latest
    container_name: doctors_wordpress
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - wordpress_data:/var/www/html
      - ./wp-content/plugins/doctors-cpt:/var/www/html/wp-content/plugins/doctors-cpt
      - ./wp-content/themes/developers-theme:/var/www/html/wp-content/themes/developers-theme

  wpcli:
    image: wordpress:cli
    container_name: doctors_wpcli
    depends_on:
      - db
      - wordpress
    volumes:
      - wordpress_data:/var/www/html
      - ./wp-content/plugins/doctors-cpt:/var/www/html/wp-content/plugins/doctors-cpt
      - ./wp-content/themes/developers-theme:/var/www/html/wp-content/themes/developers-theme
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    user: "33:33"
    command: sh -c "sleep infinity"

volumes:
  db_data:
  wordpress_data:
'''
    
    with open("docker-compose.yml", "w", encoding="utf-8", newline="\n") as f:
        f.write(content)
    
    print("[+] docker-compose.yml создан")
    
    # Запуск
    print()
    print("[*] Запуск контейнеров...")
    subprocess.run("docker-compose up -d", shell=True)
    
    print()
    print("=" * 50)
    print("     Контейнеры запущены!")
    print("=" * 50)
    print()
    print("  Подождите 1-2 минуты пока MySQL запустится,")
    print("  затем запустите: python install.py")
    print()
    print("  Проверить статус: docker-compose ps")
    print("  Логи MySQL:       docker logs doctors_db")
    print()
    print("=" * 50)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\nПрервано.")
        sys.exit(1)
    
    input("\nНажмите Enter для выхода...")