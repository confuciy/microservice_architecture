# ДЗ: Инфраструктурные паттерны

## Окружение
#### DockerHub
- https://hub.docker.com/r/confuciy/otus-lesson-4-user-service

#### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-4
- `git clone https://github.com/confuciy/microservice_architecture.git <your_folder>`

#### PostgreSQL
Установлен снаружи кластера в WSL Ubuntu.

Запрос для миграции:
```
CREATE TABLE IF NOT EXISTS ".getenv('db_schema').".users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL
);
```
db_schema - значение из ./chart/values.yaml

#### Настройки
Прописываем в C:\Windows\System32\drivers\etc\hosts:
```
172.26.222.236 arch.homework
```

Прописываем IP WSL Ubuntu, как в моем случае.

Узнаем IP следующей командой:
```
ip addr show eth0 | grep "inet " | awk '{print $2}' | cut -d'/' -f1
```

В WSL Ubuntu прописываем следующее перенаправление внешних запросов на Minikube:
```
# Перенаправление трафика
sudo iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination 192.168.49.2:80
sudo iptables -A FORWARD -p tcp -d 192.168.49.2 --dport 80 -j ACCEPT

# Включение перенаправления
sudo sysctl net.ipv4.ip_forward=1
```

192.168.49.2 - IP адрес Minikube
```
# Для получени IP Minikube
minikube ip
```

#### Коллекция Postman
- postman-collection-lesson-4.json
- base_url = http://arch.homework (для newman изменен на 192.168.49.2)
- id = 1


## Запуск приложения
Создание namespace user-service:
```
kubectl create namespace user-service
```

Запускаем приложение в namespace:
```
helm install user-service-helm ./chart --namespace=user-service
```

Проверяем запуск приложения:
```
kubectl get pods -o wide -n user-service
```

Останавливаем приложение:
```
helm uninstall user-service-helm -n user-service
```

## Смотрим работу

health-check: 

http://arch.homework/health

Ответ: 
```
{
    "status": "ok"
}
```

--

[POST] Создание пользователя: 

http://arch.homework/user
Запрос: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey",
    "lastName": "Gorbachev",
    "email": "email@email.com",
    "phone": "+71112223344"
}
```
Ответ: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey",
    "lastName": "Gorbachev",
    "email": "email@email.com",
    "phone": "+71112223344"
    "id": {{new_user_id}}
}
```
{{new_user_id}} - id добавленного пользователя в таблице users, устанавливается в качестве переменной.


[GET] Получение пользователя: 

http://arch.homework/user/{{new_user_id}}
Ответ: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey",
    "lastName": "Gorbachev",
    "email": "email@email.com",
    "phone": "+71112223344"
    "id": {{new_user_id}}
}
```

[PUT] Изменение пользователя: 

http://arch.homework/user/{{new_user_id}}
Запрос: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey Vladimirovich",
    "lastName": "Gorbachev",
    "email": "email@email.com",
    "phone": "+71112223344"
}
```
Ответ: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey Vladimirovich",
    "lastName": "Gorbachev",
    "email": "email@email.com",
    "phone": "+71112223344"
}
```

[GET] Получение пользователя - повтор: 

http://arch.homework/user/{{new_user_id}}
Ответ: 
```
{
    "username": "gorbachev",
    "firstName": "Aleksey",
    "lastName": "Gorbachev Vladimirovich",
    "email": "email@email.com",
    "phone": "+71112223344"
}
```

[DELETE] Удаление пользователя: 

http://arch.homework/user/{{new_user_id}}
Ответ: 
```
{
    "id": {{new_user_id}}
}
```

##Пример работы:

PostgreSQL перед запуском приложения
![no_table.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/no_table.png)

Запуск приложения
![helm_chart_install.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/helm_chart_install.png)

Приложение запущено
![pods_list.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/pods_list.png)

Миграция отработала
![empty_table.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/empty_table.png)

##Методы
[GET] /health
![postman_health.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_health.png)

[POST] Создание пользователя /user
![postman_user_add_1.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_add_1.png)

![postman_user_add_2.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_add_1.png)

![postman_user_add_3.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_add_3.png)

[GET] Получение пользователя /user/{{id}}
![postman_user_get_1.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_get_1.png)

[PUT] Обновление пользователя /user/{{id}}
![postman_user_update_1.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_update_1.png)

![postman_user_update_2.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_update_2.png)

[GET] Получение пользователя после обновления /user/{{id}}
![postman_user_get_after_update_1.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_get_after_update_1.png)

[DELETE] Удаление пользователя /user/{{id}}
![postman_user_delete_1.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_delete_1.png)

![postman_user_delete_2.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/postman_user_delete_2.png)

##Автоматическое тестирование - newman
![newman_test.png](https://github.com/confuciy/microservice_architecture/tree/main/lesson-4/img/newman_test.png)
