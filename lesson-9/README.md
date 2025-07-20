# ДЗ: Идемпотентость и коммутативность API в HTTP и очередях

## Окружение
#### DockerHub
- https://hub.docker.com/repository/docker/confuciy/otus-lesson-9-idempotency/general
- Приложение собрано из нескольких сервисов, каждый из своего образа.
```
Образы:
docker pull confuciy/otus-lesson-9-idempotency:php-app
docker pull confuciy/otus-lesson-9-idempotency:service-user
docker pull confuciy/otus-lesson-9-idempotency:service-auth
docker pull confuciy/otus-lesson-9-idempotency:service-notification
docker pull confuciy/otus-lesson-9-idempotency:service-order
docker pull confuciy/otus-lesson-9-idempotency:service-billing
docker pull confuciy/otus-lesson-9-idempotency:service-warehouse
docker pull confuciy/otus-lesson-9-idempotency:service-delivery
```
Для удобства все образы создаются из одного Dockerfile с разбивкой на target.
```
Например:
docker build -t confuciy/otus-lesson-9-idempotency:service-user --target=service-user .
```

![pods_1.png](./img/pods_1.png)

#### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-9
- `git clone https://github.com/confuciy/microservice_architecture.git <your_folder>`

#### Настройки
Прописываем в C:\Windows\System32\drivers\etc\hosts:
```
185.236.28.169 arch.homework
```
##### Как работает:
```
IP белый, так что приложение можно попробовать через интернет (работает не всегда, иногда неттоп перегревается и виснет). 
Роутер перенаправляет внешние запросы на неттоп в домашней сети.
На неттопе поднят Nginx, который проксирует внешние запросы на Minikube. 
В Minikube происходит вся магия, ответ возвращается пользователю.
```

##### Запуск и остановка приложения:
```
Запуск, namespace default:
helm install php-app ./helm
```
```
Остановка:
helm uninstall php-app
```

## HASH идемпотентности
- Для исключения создания повторного создания заказа проверяется его HASH;
- Если HASH уже есть в базе данных, пользователю предлагается подтвердить создание заказа (позволяет не терять клиентов, показано дальше);
- Если пользователь отказывается от повторного создания, заказ не создается;

## Приложение

#### Описание архитектурного решения и схема взаимодействия сервисов

![interaction_of_services_2.png](./img/interaction_of_services.png)

### Swagger (OpenAPI)

Методы сервисов описаны в документации - http://arch.homework/api/documentation/

![swagger_openapi_1.png](./img/swagger_openapi_1.png)

### Тесты Postman
#### Коллекция Postman
- postman-collection.json

```
newman run postman_collection.json --delay-request 100
```

![newman_1.png](./img/newman_1.png)

### Сценарий:

- конечное состояние в интерфейсе пользователя;
![site_2.png](./img/site_2.png)

- вход пользователя;
![postman_1.png](./img/postman_1.png)

- просмотр баланса биллинг-аккаунта;
![postman_2.png](./img/postman_2.png)

- создание заказа с 2 товарами;
![postman_3.png](./img/postman_3.png)

- просмотр баланса биллинг-аккаунта;
![postman_4.png](./img/postman_4.png)

- повторное создание заказа с 2 товарами;
- здесь показано, как отработает сервис, если пользователь не подтвердит повторное создание заказа через интерфейс, по все равно сделает запрос;
![postman_5.png](./img/postman_5.png)

- повторное создание заказа с 2 товарами;
- здесь показано, как отработает сервис, если пользователь подтвердит повторное создание заказа через интерфейс или в запросе;
![postman_6.png](./img/postman_6.png)

- интерфейс подтверждения повторного создания заказа;
![site_1.png](./img/site_1.png)

- теперь в базе данных два одинаковых заказа, как и хотел пользователь;
![db_1.png](./img/db_1.png)

- просмотр баланса биллинг-аккаунта;
![postman_7.png](./img/postman_7.png)

- просмотр оповещений;
![postman_8.png](./img/postman_8.png)
![postman_8.txt](./img/postman_8.txt)