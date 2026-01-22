#!/usr/bin/env python3
"""
Установка WordPress и создание контента для Doctors CPT
"""

import subprocess
import sys
import os
import time

class Installer:
    def __init__(self):
        self.project_dir = os.path.dirname(os.path.abspath(__file__))
        os.chdir(self.project_dir)
    
    def log(self, msg, level="INFO"):
        symbols = {"INFO": "[*]", "SUCCESS": "[+]", "ERROR": "[-]", "WARN": "[!]"}
        print(f"{symbols.get(level, '[*]')} {msg}")
    
    def run(self, cmd):
        try:
            result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=120)
            return result.returncode == 0
        except:
            return False
    
    def run_get(self, cmd):
        try:
            result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=60)
            return result.stdout.strip()
        except:
            return ""
    
    def wp(self, cmd):
        return self.run(f'docker exec doctors_wpcli wp {cmd}')
    
    def wp_get(self, cmd):
        return self.run_get(f'docker exec doctors_wpcli wp {cmd}')
    
    def check_connection(self):
        self.log("Проверка подключения к БД...")
        result = self.run_get('docker exec doctors_db mysqladmin ping -uroot -prootpassword 2>&1')
        if "alive" in result.lower():
            self.log("MySQL работает", "SUCCESS")
            return True
        self.log("MySQL не готов", "ERROR")
        return False
    
    def install_wordpress(self):
        self.log("Установка WordPress...")
        self.wp('core install --url="http://localhost:8080" --title="Doctors" --admin_user="admin" --admin_password="admin123" --admin_email="admin@test.com" --skip-email')
        return True
    
    def activate_theme_plugin(self):
        self.log("Активация темы и плагина...")
        self.wp("theme activate developers-theme")
        self.wp("plugin activate doctors-cpt")
    
    def setup_htaccess_file(self):
        """Создает физический файл .htaccess с правильными правами"""
        self.log("Генерация .htaccess...")
        
        htaccess_content = """# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress"""

        # Пишем файл через bash printf для надежности
        cmd = f'docker exec doctors_wordpress bash -c "printf \'{htaccess_content}\' > /var/www/html/.htaccess"'
        self.run(cmd)
        
        # Выставляем права, чтобы WP мог его обновлять, а Apache читать
        self.run("docker exec doctors_wordpress chown www-data:www-data /var/www/html/.htaccess")
        self.run("docker exec doctors_wordpress chmod 664 /var/www/html/.htaccess")
        
        self.log(".htaccess создан и права назначены", "SUCCESS")

    def create_taxonomies(self):
        self.log("Создание таксономий...")
        self.wp("post delete 1 2 3 --force")
        
        specs = [("Терапевт", "therapist"), ("Кардиолог", "cardiologist"), ("Невролог", "neurologist"),
                 ("Хирург", "surgeon"), ("Педиатр", "pediatrician"), ("Офтальмолог", "ophthalmologist"),
                 ("Дерматолог", "dermatologist"), ("Эндокринолог", "endocrinologist")]
        for name, slug in specs:
            self.wp(f'term create specialization "{name}" --slug={slug}')
        
        cities = [("Москва", "moscow"), ("Санкт-Петербург", "spb"), ("Новосибирск", "novosibirsk"),
                  ("Екатеринбург", "ekaterinburg"), ("Казань", "kazan")]
        for name, slug in cities:
            self.wp(f'term create city "{name}" --slug={slug}')
    
    def create_doctors(self):
        self.log("Создание докторов...")
        doctors = [
            ("Иванов Иван Иванович", "cardiologist therapist", "moscow", 15, 3500, 4.8),
            ("Петрова Мария Сергеевна", "pediatrician", "moscow", 12, 2500, 4.9),
            ("Сидоров Алексей Петрович", "surgeon neurologist", "spb", 20, 5000, 4.7),
            ("Козлова Елена Викторовна", "dermatologist", "moscow", 8, 2800, 4.6),
            ("Михайлов Дмитрий Олегович", "ophthalmologist surgeon", "ekaterinburg", 14, 3200, 4.5),
            ("Новикова Анна Александровна", "endocrinologist therapist", "kazan", 10, 2700, 4.8),
            ("Васильев Сергей Николаевич", "therapist", "novosibirsk", 18, 2000, 4.4),
            ("Фёдорова Ольга Игоревна", "neurologist", "spb", 11, 3000, 4.7),
            ("Морозов Андрей Владимирович", "surgeon", "moscow", 25, 4500, 4.9),
            ("Кузнецова Наталья Павловна", "cardiologist", "ekaterinburg", 9, 3300, 4.6),
            ("Соколов Павел Андреевич", "pediatrician", "kazan", 7, 2400, 4.5),
            ("Белова Ирина Михайловна", "dermatologist", "spb", 13, 3100, 4.8),
        ]
        
        created = 0
        for i, (name, specs, city, exp, price, rating) in enumerate(doctors, 1):
            print(f"    {i}/12 {name}...", end=" ", flush=True)
            post_id = self.wp_get(f'post create --post_type=doctors --post_title="{name}" --post_status=publish --post_content="Опытный специалист." --post_excerpt="Врач высшей категории." --porcelain')
            if post_id and post_id.strip().isdigit():
                pid = post_id.strip()
                self.wp(f'post term add {pid} specialization {specs}')
                self.wp(f'post term add {pid} city {city}')
                self.wp(f'post meta update {pid} _doctor_experience {exp}')
                self.wp(f'post meta update {pid} _doctor_price_from {price}')
                self.wp(f'post meta update {pid} _doctor_rating {rating}')
                print("OK")
                created += 1
            else:
                print("SKIP")
        self.log(f"Создано: {created}/12", "SUCCESS")

    def fix_404_magic(self):
        """
        Эмуляция нажатия кнопки 'Сохранить изменения' в админке.
        Это единственный надежный способ исправить 404 для CPT.
        """
        self.log("Фикс ошибки 404 (обновление правил)...")
        
        # 1. Удаляем закэшированные правила из БД (самое важное!)
        self.wp("option delete rewrite_rules")
        
        # 2. Устанавливаем структуру еще раз (триггерит хуки WP)
        self.wp('rewrite structure "/%postname%/"')
        
        # 3. Сбрасываем объектный кэш
        self.wp("cache flush")
        
        # 4. Жесткий сброс (генерирует правила заново)
        self.wp("rewrite flush --hard")
        
        self.log("Правила обновлены", "SUCCESS")

    def show_result(self):
        print("\n" + "=" * 55)
        print("        УСТАНОВКА ЗАВЕРШЕНА!")
        print("=" * 55)
        print("  Главная:  http://localhost:8080/")
        print("  Доктора:  http://localhost:8080/doctors/")
        print("  Админка:  http://localhost:8080/wp-admin/")
        print("  Логин:    admin")
        print("  Пароль:   admin123")
        print("=" * 55)
    
    def run_install(self):
        print("\n" + "=" * 55)
        print("     Установка WordPress Doctors CPT")
        print("=" * 55 + "\n")
        
        if not self.check_connection():
            print("\nMySQL не готов. Подождите и запустите снова: python install.py")
            return False
        
        self.install_wordpress()
        self.activate_theme_plugin()
        
        # 1. Сначала создаем файл физически
        self.setup_htaccess_file()
        
        self.create_taxonomies()
        self.create_doctors()
        
        # 2. В самом конце применяем магию фикса 404
        self.fix_404_magic()
        
        self.show_result()
        return True

if __name__ == "__main__":
    installer = Installer()
    try:
        installer.run_install()
    except KeyboardInterrupt:
        sys.exit(1)
    except Exception as e:
        print(f"\nОшибка: {e}")
        sys.exit(1)
    
    input("\nНажмите Enter для выхода...")