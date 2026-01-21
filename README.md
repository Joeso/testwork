# WordPress Doctors CPT
## Вакансия WordPress Developer / Web разработчик
## Описание проекта

Решение тестового задания: кастомный тип записей "Доктор" с таксономиями, мета-полями, 
архивным выводом, фильтрацией и пагинацией.

## Быстрый старт
Команды Docker
docker-compose up -d      # Запуск
docker-compose down       # Остановка
docker-compose logs -f    # Логи


WordPress: http://localhost:8080
Админка: http://localhost:8080/wp-admin
Архив докторов: http://localhost:8080/doctors/
Логин/пароль: admin / admin123
Структура проекта
wordpress-doctors/
+-- docker-compose.yml
+-- wp-content/
|   +-- plugins/
|   |   +-- doctors-cpt/           # Плагин CPT
|   +-- themes/
|       +-- developers-theme/      # Тема
+-- demo-data/
    +-- create-demo-data.sh        # Скрипт демо-данных
    Архитектурные решения
1. pre_get_posts vs WP_Query
Используется pre_get_posts для фильтрации архива:

Модифицируем основной запрос - пагинация работает автоматически
Один запрос к БД вместо двух
2. Таксономия "Город" - hierarchical
Выбрано hierarchical (как рубрики):

Можно группировать по регионам
Единообразие написания - выбор из списка
Нет дубликатов
3. Мета-боксы без ACF
Реализованы нативные WordPress meta boxes:

Не требует дополнительных плагинов
Полный контроль над санитизацией
URL для проверки
URL	Описание
/doctors/	Архив докторов
/doctors/?specialization=cardiologist	Фильтр по специализации
/doctors/?city=moscow	Фильтр по городу
/doctors/?sort=price_asc	Сортировка по цене
GET-параметры фильтрации
Параметр	Значения
specialization	slug термина
city	slug термина
sort	rating_desc, price_asc, experience_desc, date_desc, title_asc
paged	число
