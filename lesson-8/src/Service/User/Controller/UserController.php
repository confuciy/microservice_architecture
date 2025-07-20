<?php
namespace App\Service\User\Controller;

use App\Service\User\Model\User;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="User | Сервис пользователей"
 * )
 */
class UserController
{
    private $user;
    private $helper;

    public function __construct()
    {
        $this->user = new User();
        $this->helper = new Helper();
    }

    # Страница редактирования профиля пользователя
    public function edit()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            $this->helper->getHeader();

            echo '<body>';

            echo '<style>body, div, p {margin: 0}</style>';

            echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Редактирование профиля пользователя</h1>
                </div>
            </div>';

            echo '<div style="padding: 10px;">';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';
                echo '<br><br>';

                echo '<form method="post" action="/user/update">';
                    echo '<input type="hidden" name="reload" value="1">';
                    echo '<table cellpadding="5" cellspacing="1" border="1">';
                    echo '<tr>';
                        echo '<td><b>Id</b></td>';
                        echo '<td>'.$user['user_id'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Last Name</b></td>';
                        echo '<td><input type="text" name="last_name" value="'.htmlentities($user['last_name'], ENT_QUOTES, 'UTF-8').'"></td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>First Name</b></td>';
                        echo '<td><input type="text" name="first_name" value="'.htmlentities($user['first_name'], ENT_QUOTES, 'UTF-8').'"></td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Email</b></td>';
                        echo '<td>'.$user['email'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Phone</b></td>';
                        echo '<td><input type="text" name="phone" value="'.htmlentities($user['phone'], ENT_QUOTES, 'UTF-8').'"></td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Address</b></td>';
                        echo '<td><input type="text" name="address" value="'.htmlentities($user['address'], ENT_QUOTES, 'UTF-8').'" size="40"></td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td colspan="2"><input type="submit" value="Сохранить"></td>';
                    echo '</tr>';
                    echo '</table>';
                echo '</form>';

            echo '</div>';

            echo '</body>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    # Страница с профилем пользователя
    public function profile()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            $this->helper->getHeader();

            echo '<body>';

            echo '<style>body, div, p {margin: 0}</style>';
            echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Профиль пользователя</h1>
                </div>
            </div>';

            echo '<div style="padding: 10px;">';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';
                echo '<br><br>';

                echo '<table cellpadding="5" cellspacing="1" border="1">';
                    echo '<tr>';
                        echo '<td><b>Id</b></td>';
                        echo '<td>'.$user['user_id'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Last Name</b></td>';
                        echo '<td>'.$user['last_name'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>First Name</b></td>';
                        echo '<td>'.$user['first_name'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Email</b></td>';
                        echo '<td>'.$user['email'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Phone</b></td>';
                        echo '<td>'.$user['phone'].'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td><b>Address</b></td>';
                        echo '<td>'.$user['address'].'</td>';
                    echo '</tr>';
                echo '</table>';

            echo '</div>';

            echo '</body>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    # Страница с регистрацией
    public function register()
    {
        $this->helper->getHeader();

        echo '<body>';

        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Регистрация пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            echo '<form method="post" action="/user/create">';
                echo '<input type="hidden" name="reload" value="1">';
                echo 'Email:<br><input name="email" type="text"><br>';
                echo 'Password:<br><input name="password" type="password"><br>';
                echo '<br><br>';
                echo 'Last Name:<br><input name="last_name" type="text"><br>';
                echo 'First Name:<br><input name="first_name" type="text"><br>';
                echo 'Phone:<br><input name="phone" type="text"><br>';
                echo 'Address:<br><input name="address" type="text" size="40"><br>';
                echo '<br>';
                echo '<input type="submit" value="Отправить">';
            echo '</form>';

        echo '</div>';

        echo '</body>';

        return;
    }

    # Страница с логином
    public function login()
    {
        $this->helper->getHeader();

        echo '<body>';

        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Авторизация пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            echo '<form method="post" action="/user/auth">';
                echo '<input type="hidden" name="reload" value="1">';
                echo 'Email:<br><input name="email" type="text"><br>';
                echo 'Password:<br><input name="password" type="password"><br>';
                echo '<br>';
                echo '<input type="submit" value="Отправить">';
            echo '</form>';

        echo '</div>';

        echo '</body>';

        return;
    }

    # Страница c уведомлениями пользователя
    public function notification()
    {
        $this->helper->getHeader();

        echo '<body>';

        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Уведомления пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            # Получаем уведомления пользователя
            $notification_list = $this->user->getNotificationList();

            if (isset($notification_list['notification_list']) and sizeof($notification_list['notification_list']) > 0) {

                $col = 1;

                echo '<table cellpadding="5" cellspacing="1" border="1">';

                    echo '<tr>';
                        echo '<td style="text-align: center; font-weight: bold;">#</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Действие</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Сообщение</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Дата добавления</td>';
                    echo '</tr>';

                    foreach ($notification_list['notification_list'] as $notification) {

                        echo '<tr>';
                            echo '<td>'.$col.'</td>';
                            echo '<td>'.$notification['action'].'</td>';
                            echo '<td>'.$notification['message'].'</td>';
                            echo '<td>'.date('d.m.Y H:i:s', strtotime(substr($notification['date_insert'], 0, 19))).'</td>';
                        echo '</tr>';

                        $col++;
                    }

                echo '</table>';
            }

        echo '</div>';

        echo '</body>';

        return;
    }

    # Страница c заказами
    public function order()
    {
        $this->helper->getHeader();

        echo '<body>';

        echo '<style>
            
            .columns {
                display: inline-grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-gap: 10px;
                
                /* (A2) OPTIONAL WIDTH RESTRICT */
                margin: 0 auto;
                overflow: hidden;
            }
            
            .columns div.warehouse, .columns div.orders {
                /* (B1) DIMENSION */
                width: 98%;
                padding: 10px;
                padding-top: 0;
                
                /* (B3) IMAGE RESIZE */
                /* cover | contain | fill | scale-down */
                object-fit: cover;
            }

            .items {
                display: inline-grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                grid-gap: 10px;
                
                /* (A2) OPTIONAL WIDTH RESTRICT */
                max-width: 1200px;
                margin: 0 auto;
                overflow: hidden;
                padding-bottom: 30px;
            }
            
            .items div {
                /* (B1) DIMENSION */
                width: 100%;
                padding: 10px;
                
                /* (B2) COLORS */
                border: 1px solid #ddd;
                background: #fff;
                
                /* (B3) IMAGE RESIZE */
                /* cover | contain | fill | scale-down */
                object-fit: cover;
            }
            
            .items img {
                /* (B1) DIMENSION */
                height: 149px; /* optional */
                
                /* (B3) IMAGE RESIZE */
                /* cover | contain | fill | scale-down */
                object-fit: cover;
            }
        </style>';

        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Заказы</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

                # Получаем биллинг-аккаунт
                $billing = $this->user->getBilling();

                if (sizeof($billing) > 0) {

                    echo '<div style="padding: 5px; background: lightgrey;">
                            Текущая сумма биллинг-аккаунта: <span style="font-size: 14px; font-weight: bold;" id="billing_amount">'.$billing['amount'].'</span>
                        </div>';
                    echo '<br><br>';
                }

                echo '<form method="post" action="/order">';
                    echo '<input type="hidden" name="reload" value="1">';
                    echo '<input type="hidden" name="idempotency" value="xxx">';

                    echo '<div class="columns">';

                        echo '<div class="warehouse">';

                            echo '<h2 style="margin: 0; margin-bottom: 10px;">Товары</h2>';

                            echo '<div class="items">';

                                # Получаем список товаров
                                $warehouse_list = $this->user->getWarehouseList();

                                foreach ($warehouse_list as $warehouse) {

                                    # Если товара нет на складе
                                    if (empty($warehouse['count'])) {
                                        continue;
                                    }

                                    echo '<div data-id="'.$warehouse['warehouse_id'].'">';
                                        echo '<img src="/img/'.$warehouse['photo'].'">';
                                        echo '<br>';
                                        echo '<span id="warehouse-name"><b>'.$warehouse['descr'].'</b></span>';
                                        echo '<br><br>';
                                        echo '<span id="warehouse-price"><b>'.$warehouse['price'].'</b>₽</span>';
                                        echo '<br><br>';
                                        echo '<span id="warehouse-count" style="color: #5e626f;">осталось на складе: <b>'.$warehouse['count'].'</b></span>';
                                        echo '<br><br>';
                                        echo '<span id="warehouse_button_'.$warehouse['warehouse_id'].'" onclick="setBasket(this);" 
                                            style="border: 1px solid gray; border-radius: 3px; padding: 2px 5px;">Добавить в корзину</span>';
                                    echo '</div>';
                                }

                            echo '</div>';

                            echo '<div id="basket" style="display: none; margin-left: 15px;">';

                                echo 'Корзина:';

                                echo '<div id="warehouse-item-list" style="margin-top: 10px;"></div>';

                                echo '<div style="margin-top: 30px;">';
                                    echo '<input type="button" value="Создать заказ" onclick="checkOrder();">';
                                echo '</div>';

                            echo '</div>';

                        echo '</div>';

                        echo '<div class="orders">';

                            echo '<h2 style="margin: 0; margin-bottom: 10px;">Заказы</h2>';

                            # Получаем заказы пользователя
                            $order_list = $this->user->getOrderList();

                            if (isset($order_list['order_list']) and sizeof($order_list['order_list']) > 0) {

                                $col = 1;

                                $order_status = [
                                    0 => "новый",
                                    1 => "ожидает оплаты",
                                    2 => "оплачен",
                                    3 => "ожидает доставки",
                                    5 => "доставлен",
                                    6 => "отменен"
                                ];

                                foreach ($order_list['order_list'] as $order) {

                                    echo '<div style="border: 1px solid #ddd; padding: 10px;">';

                                        echo '<b>Заказ №' . $order['order_id'] . ' от ' . date('d.m.Y H:i:s', strtotime(substr($order['date_insert'], 0, 19))) . '</b><br>';
                                        echo '<span style="color: #cccccc; font-size: 11px;">' . $order['idempotency'] . '</span><br><br>';

                                        echo 'Статус: <b>' . $order_status[$order['status']] . '</b><br>';
                                        echo 'Стоимость: <b>' . $order['amount'] . '</b>₽<br><br>';

                                        if ($order['status'] >= 3) {

                                            if ($order['status'] == 6) {

                                                echo '<span style="color: red;">' . $order['error_text'] . '</span>';

                                                echo '<br><br>';
                                            }

                                            echo 'Товары:<br>';
                                            echo '<div style="display: inline-grid">';
                                                foreach ($order['warehouse_action_list'] as $warehouse_action) {
                                                    echo '<div style="clear: both;">';
                                                    echo '<div style="float: left;"><img src="/img/' . $warehouse_action['photo'] . '" width="40"></div>';
                                                    echo '<div style="float: left; padding: 5px 10px;">' . $warehouse_action['descr'] . ' <small>х</small> ' . $warehouse_action['count'] . ' = <b>' . $warehouse_action['price_total'] . '</b>₽</div>';
                                                    echo '</div>';
                                                }
                                            echo '</div>';

                                        } else {

                                            echo '<span style="color: #5e626f;">Товары появятся, после того, как будут успешно зарезервированы.</span>';
                                        }

                                    echo '</div>';
                                }

                            } else {

                                echo 'Заказов еще нет';
                            }

                        echo '</div>';

                    echo '</div>';

                echo '</form>';

            echo '</div>';

            echo '
            <script>
            
            // Элементы карзины
            var warehouse_item_list = [];
            
            // Создание заказа
            function createOrder(idempotency) {
              
                // Счетчик
                var count_total = 0;
                
                // Элементы карзины
                var order_item_list = [];
                
                Object.keys(warehouse_item_list).forEach(function(key) {  
                    
                    if(warehouse_item_list[key].name) { 
                        order_item_list[count_total] = warehouse_item_list[key];
                        count_total += 1;
                    }
                });
                
                if (count_total > 0) {
               
                    $.ajax({
                        url: "/order",
                        dataType: "json",
                        type: "POST",
                        data: ({ "warehouse_list": JSON.stringify(order_item_list) }),
                        async: false,
                        xhrFields: {
                            withCredentials: true // Ключевая опция для отправки куки
                        },
                        success: function(data) {
                        
                            location.reload();                            
                        },
                        error: function (xhr) {
    
                            try {
                                 var response = JSON.parse(xhr.responseText);
                                 if (response.error) {
                                     alert(response.error);
                                 } else {
                                     alert(xhr.responseText);
                                 }
                             } catch (e) {
                                 alert(xhr.responseText);
                             }
                        }
                    });
                    
                } else {
                
                    alert("Добавьте товар в корзину");
                }
            }
            
            // Проверка заказа
            function checkOrder() {
              
                // Счетчик
                var count_total = 0;
                
                // Элементы карзины
                var order_item_list = [];
                
                // Ответ проверки заказа
                var order_check = true;
                
                Object.keys(warehouse_item_list).forEach(function(key) {  
                    
                    if(warehouse_item_list[key].name) { 
                        order_item_list[count_total] = warehouse_item_list[key];
                        count_total += 1;
                    }
                });
                
                if (count_total > 0) {
               
                    $.ajax({
                        url: "/order/check",
                        dataType: "json",
                        type: "POST",
                        data: ({ "warehouse_list": JSON.stringify(order_item_list) }),
                        async: false,
                        xhrFields: {
                            withCredentials: true // Ключевая опция для отправки куки
                        },
                        success: function(data) {
    
                            // Сохраняем ответ проверки заказа
                            order_check = data.order_check;
    
                            // Проверка заказа не прошла
                            if (data.order_check == false) {
                            
                                alert("Подобный заказ был создан менее 5 минут назад. Пожалуйста, подождите!");
                            
                            // Создание заказа
                            } else {
                            
                                createOrder(data.idempotency);
                            }
                        },
                        error: function (xhr) {
    
                            try {
                                 var response = JSON.parse(xhr.responseText);
                                 if (response.error) {
                                     alert(response.error);
                                 } else {
                                     alert(xhr.responseText);
                                 }
                             } catch (e) {
                                 alert(xhr.responseText);
                             }
                        }
                    });
                    
                } else {
                
                    alert("Добавьте товар в корзину");
                }
            }
            
            // Заполняем элемент карзины
            function setBasket(e)
            {
                var e = e;
                
                var warehouse_id = $(e).closest("div").data("id");
                var warehouse_name = $(e).closest("div").find("span[id=\'warehouse-name\']").text();
                var warehouse_price = $(e).closest("div").find("span[id=\'warehouse-price\']").find("b").text();
                
                if (!warehouse_item_list[warehouse_id]) {
                
                    var basket_element = {};
                
                } else {
                
                    var basket_element = warehouse_item_list[warehouse_id];
                }
                
                basket_element.warehouse_id = warehouse_id;
                basket_element.name = warehouse_name;
                basket_element.price = warehouse_price;
                if (!basket_element.count) {
                
                    basket_element.count = 1;
                
                } else {
                    basket_element.count++;
                }
                
                if ($(e).text() == "Добавить в корзину") {
                    
                    $(e).text("Добавить еще один");
                    $(e).css("background", "lightgray");
                }
                
                // Добавляем элемент в список карзины
                warehouse_item_list[warehouse_id] = basket_element;
            
                // Рассчитываем карзину
                showBasket();
                
                //alert(warehouse_id + " / " + warehouse_item_list[warehouse_id].name + " / " + warehouse_item_list[warehouse_id].price + " / " + warehouse_item_list[warehouse_id].count);
            }
    
            // Рассчитываем карзину
            function showBasket() {
            
                var price_total = 0;
                var count_total = 0;
                var basket_html = "";
                
                // Формируем HTML карзины
                Object.keys(warehouse_item_list).forEach(function(key) {  
                    
                    if (warehouse_item_list[key].count == 0) {
                    
                        // warehouse-button-\'.$warehouse[\'warehouse_id\']
                        
                        $("#warehouse_button_" + key).text("Добавить в корзину");
                        $("#warehouse_button_" + key).css("background", "white");
                    
                    } else { 
                    
                        basket_html = basket_html + "<span id=\"basket_item_" + key + "\">" + warehouse_item_list[key].name + " <small>х</small> <span>" + warehouse_item_list[key].count + "</span> = <b>" + warehouse_item_list[key].price + "</b>₽ <span style=\"cursor: pointer;\" onclick=\"deleteBasket(this, " + key + ");\">[X]</span></span><br>";
                        
                        price_total += warehouse_item_list[key].count * warehouse_item_list[key].price;
                         
                        count_total += warehouse_item_list[key].count;
                    }
                });
                
                if (count_total > 0) {
                
                    basket_html = basket_html + "<br>Итого: <b>" + price_total + "</b>₽";
                    
                    // Записываем HTML корзины
                    $("#warehouse-item-list").html(basket_html);
                    
                    // Показываем карзину
                    $("#basket").show();
                
                } else {
                
                    // Записываем HTML корзины
                    $("#warehouse-item-list").html("");
                    
                    // Показываем карзину
                    $("#basket").hide();
                }
            }
            
            // Удаляем элемент карзины
            function deleteBasket(e, warehouse_id) {
            
                var e = e;
                
                warehouse_item_list[warehouse_id].count--;
                
                $("#basket_item_" + warehouse_id).remove();
                
                // Рассчитываем карзину
                showBasket();
            }
            </script>';

        echo '</div>';

        echo '</body>';

        return;
    }

    # Страница биллинг-аккаунта
    public function billing()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Пополнение биллинг-аккаунта</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            # Получаем биллинг-аккаунт
            $billing = $this->user->getBilling();

            if (sizeof($billing) > 0) {

                echo '<div style="padding: 5px; background: lightgrey;">
                    Текущая сумма биллинг-аккаунта: <span style="font-size: 14px; font-weight: bold;">'.$billing['amount'].'</span>
                </div>';
                echo '<br><br>';
            }

            echo '<form method="post" action="/billing/amount">';
                echo '<input type="hidden" name="reload" value="1">';
                echo '<input type="hidden" name="action" value="plus">';
                echo 'Сумма пополнения:<br><input name="amount" type="text"><br>';
                echo '<br>';
                echo '<input type="submit" value="Пополнить">';
            echo '</form>';

        echo '</div>';

        return;
    }

    /**
     * @OA\Post(
     *     path="/user/auth",
     *     summary="Авторизация пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     operationId="user_auth",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(description="Email пользователя", property="email", type="string", format="string"),
     *                 @OA\Property(description="Пароль", property="password", type="string", format="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserAuthResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="UserAuthResponse",
     *     title="Авторизация пользователя",
     *     description="",
     *     @OA\Property(property="jwt_token_id", type="integer", example="1"),
     *     @OA\Property(property="jwt", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2V..."),
     *     @OA\Property(property="exp", type="integer", example="1746674949"),
     *     @OA\Property(property="user_id", type="integer", example="1")
     * )
     *
     * @throws \Exception
     */
    public function auth()
    {
        try {

            # Получаем данные
            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null and sizeof($_POST) > 0) {

                # Данные пользователя
                $data = $_POST;
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }


            if (
                !isset($data['email']) or trim($data['email']) == ''
                or !isset($data['password']) or trim($data['password']) == ''
            ) {
                http_response_code(401);
                throw new \Exception('Проверьте свой Email или пароль');
            }

            $email = trim($data['email']);
            $password = trim($data['password']);

            $user = $this->user->getUserByEmailAndPassword($email, '*'.strtoupper(sha1(sha1($password, true))));

            if (!isset($user['user_id']) or empty($user['user_id'])) {

                http_response_code(401);
                throw new \Exception('Авторизация провалилась. Проверьте свой Email или пароль');
            }

            # Создание JWT-токен пользователя
            $data = $this->user->createJWTtoken($user['user_id']);

            # Устанавливаем COOKIE
            setcookie('user_jwt', $data['jwt'], [
                'expires' => $data['exp'],
                'path' => '/',
                'domain' => getenv('domain'),
                'secure' => false,    // Для HTTPS
                'httponly' => true,  // Защита от XSS
                'samesite' => 'Lax'
            ]);

            if (isset($_POST['reload'])) {

                header('Location: /user/profile');

            } else {

                echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }

            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Get(
     *     path="/user/exit",
     *     summary="Выход пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     security={{"cookieAuth": {}}},
     *     operationId="user_exit",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @throws \Exception
     */
    public function exit()
    {
        try {

            # Выполняем выход пользователя
            $this->user->exit();

            # Удаление COOKIE
            setcookie('user_jwt', '', -1, '/', getenv('domain'));
            unset($_COOKIE['user_jwt']);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode(['message' => 'Выход произведен успешно'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

            } else {

                header('Location: /');
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Post(
     *     path="/user/create",
     *     summary="Регистрация пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     security={{"cookieAuth": {}}},
     *     operationId="user_create",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(description="Email пользователя", property="email", type="string", format="string"),
     *                 @OA\Property(description="Пароль", property="password", type="string", format="string"),
     *                 @OA\Property(description="Имя", property="first_name", type="string", format="string"),
     *                 @OA\Property(description="Фамилия", property="last_name", type="string", format="string"),
     *                 @OA\Property(description="Телефон", property="phone", type="string", format="string"),
     *                 @OA\Property(description="Адрес", property="address", type="string", format="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserCreateResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="UserCreateResponse",
     *     title="Пользователь",
     *     description="",
     *     @OA\Property(property="username", type="string", example="gorbachev"),
     *     @OA\Property(property="first_name", type="string", example="Aleksey"),
     *     @OA\Property(property="last_name", type="string", example="Gorbachev"),
     *     @OA\Property(property="email", type="string", example="email@email.com"),
     *     @OA\Property(property="password", type="string", example="12345"),
     *     @OA\Property(property="phone", type="string", example="+71112223344"),
     *     @OA\Property(property="address", type="string", example="Moscow, Red Square, 1")
     * )
     *
     * @throws \Exception
     */
    public function create()
    {
        try {

            # Получаем данные
            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null and sizeof($_POST) > 0) {

                # Данные пользователя
                $data = $_POST;
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }


            if (
                !isset($data['email']) or $data['email'] == ''
                or !isset($data['password']) or $data['password'] == ''
            ) {
                throw new \Exception('Пустой Email или пароль');
            }

            if ($this->user->checkUserExists($data['email']) == true) {
                throw new \Exception('Пользователь с Email '.$data['email'].' уже существует');
            }

            # Заполняем username
            $data['username'] = explode('@', $data['email'])[0];

            # Создаем пользователя
            $user = $this->user->create($data);

            # Добавляем оповещение
            $this->helper->setNotification($user['user_id'], 'create_user_ok', '[✓] Пользователь с почтой '.$data['email'].' создан с ID '.$user['user_id']);


            # Создаем аккаунт

            # Данные для отправки
            $data = [
                'action' => 'create',
                'data' => [
                    'user_id' => $user['user_id'],
                    'amount' => 0
                ]
            ];

            # Создаем аккаунт в сервисе биллинга
            # Отправляем сообщение в RabbitMQ
            $this->helper->rabbitmqSend('service-billing', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

            if (isset($_POST['reload'])) {

                header('Location: /user/login');

            } else {

                http_response_code(201);
                echo json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }

            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * Получение пользователя - для Postman'а
     * @param int $userId
     * @throws \Exception
     */
    public function get(int $userId)
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                throw new \Exception('Вы не авторизованы');
            }
            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                throw new \Exception('Это не Ваш профиль');
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

            } else {

                echo '<h1>arch.homework / Профиль пользователя</h1>';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';

                echo '<table cellpadding="5" cellspacing="1" border="1">';
                echo '<tr>';
                    echo '<td><b>Id</b></td>';
                    echo '<td>' . $user['user_id'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Last Name</b></td>';
                    echo '<td>' . $user['last_name'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>First Name</b></td>';
                    echo '<td>' . $user['first_name'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Email</b></td>';
                    echo '<td>' . $user['email'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Phone</b></td>';
                    echo '<td>' . $user['phone'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Address</b></td>';
                    echo '<td>'.$user['address'].'</td>';
                echo '</tr>';
                echo '</table>';
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * Обновление пользователя
     * @param int $userId
     */
    public function update(int $userId = 0)
    {
        try {

            # Получаем данные
            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null and sizeof($_POST) > 0) {

                # Данные пользователя
                $data = $_POST;
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }


            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                throw new \Exception('Вы не авторизованы');
            }

            if (empty($userId)) {
                $userId = $jwt_token_data['user_id'];
            }

            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                throw new \Exception('Это не Ваш профиль');
            }

            # Обновляем пользователя
            $this->user->update($userId, $data);

            if (isset($_POST['reload'])) {

                header('Location: /user/profile');

            } else {

                $headers = apache_request_headers();

                if (isset($headers['Postman-Token'])) {

                    http_response_code(204);

                }
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * Удаление пользователя
     * @param int $userId
     */
    public function delete(int $userId = 0)
    {
        try {

            $user = $this->user->delete($userId);

            echo json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }
}