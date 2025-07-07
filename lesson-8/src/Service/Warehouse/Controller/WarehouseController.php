<?php
namespace App\Service\Warehouse\Controller;

use App\Service\Warehouse\Model\Warehouse;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="Warehouse | Сервис склада"
 * )
 */
class WarehouseController
{
    private $warehouse;
    private $helper;

    public function __construct()
    {
        $this->warehouse = new Warehouse();
        $this->helper = new Helper();
    }

    /**
     * @OA\Post(
     *     path="/warehouse",
     *     summary="Резервироние товара",
     *     description="",
     *     tags={"Warehouse | Сервис склада"},
     *     security={{"cookieAuth": {}}},
     *     operationId="warehouse_create",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"order_id", "warehouse_list"},
     *                 @OA\Property(description="ID заказа", property="order_id", type="integer", format="integer"),
     *                 @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseCreateItem"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/WarehouseCreateResponse")
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
     *     schema="WarehouseCreateResponse",
     *     title="Резервироние товара",
     *     description="",
     *     @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseCreateItem")),
     *     @OA\Property(property="warehouse_action_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseActionCreateItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseCreateItem",
     *     title="Список товаров",
     *     description="",
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="count", type="number", example="1")
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseActionCreateItem",
     *     title="Зарезервированный товар заказа",
     *     description="",
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="action", type="string", example="plus"),
     *     @OA\Property(property="count", type="number", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="status", type="integer", example="1")
     * )
     *
     * @throws \Exception
     */
    public function create(array $data = [])
    {
        try {

            # Получаем данные
            if (!sizeof($data)) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null and sizeof($_POST) > 0) {

                    # Данные пользователя
                    $data = $_POST;
                }
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }

            if (!isset($data['warehouse_id']) or empty($data['warehouse_id'])) {
                throw new \Exception('ID товара на складе пустой');
            }
            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('Не удалось определить ID заказа');
            }

            # Проверка достаточного кол-ва товара на складе
            if ($this->warehouse->checkWarehouseListCount($data) == 0) {

                $warehouse = $this->warehouse->create($data);

                http_response_code(201);
                return json_encode($warehouse);

            } else {

                throw new \Exception('На складе недостаточно товара');
            }

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @OA\Get(
     *     path="/warehouse",
     *     summary="Список товаров на складе",
     *     description="",
     *     tags={"Warehouse | Сервис склада"},
     *     security={{"cookieAuth": {}}},
     *     operationId="warehouse_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/WarehouseGetResponse")
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
     *     schema="WarehouseGetResponse",
     *     title="Список товаров на складе",
     *     description="",
     *     @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseGetItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseGetItem",
     *     title="Зарезервированный товар заказа",
     *     description="",
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="price", type="number", example="100.25"),
     *     @OA\Property(property="descr", type="string", example="Лампа"),
     *     @OA\Property(property="photo", type="string", example="lamp.jpg"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807")
     * )
     *
     * @throws \Exception
     */
    public function get()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {
                http_response_code(401);
                throw new \Exception('Пользователь не авторизован');
            }

            $warehouse_list = $this->warehouse->getWarehouseList();

            http_response_code(200);
            echo json_encode($warehouse_list);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @OA\Get(
     *     path="/warehouse/order",
     *     summary="Получение списка зарезервированных товаров заказа",
     *     description="",
     *     tags={"Warehouse | Сервис склада"},
     *     security={{"cookieAuth": {}}},
     *     operationId="warehouse_order",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"order_id"},
     *                 @OA\Property(description="ID заказа", property="order_id", type="integer", format="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/WarehouseOrderGetResponse")
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
     *     schema="WarehouseOrderGetResponse",
     *     title="Зарезервированный товар заказа",
     *     description="",
     *     @OA\Property(property="warehouse_action_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseActionGetItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseActionGetItem",
     *     title="Зарезервированный товар заказа",
     *     description="",
     *     @OA\Property(property="warehouse_action_id", type="integer", example="1"),
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="action", type="string", example="plus"),
     *     @OA\Property(property="count", type="number", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="price", type="number", example="100.25"),
     *     @OA\Property(property="descr", type="string", example="Лампа"),
     *     @OA\Property(property="photo", type="string", example="lamp.jpg"),
     *     @OA\Property(property="price_total", type="number", example="100.25")
     * )
     *
     * @throws \Exception
     */
    public function order(array $data = [])
    {
        try {

            # Получаем данные
            if (!sizeof($data)) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null and sizeof($_POST) > 0) {

                    # Данные пользователя
                    $data = $_POST;
                }
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }

            $order_list = $this->warehouse->getWarehouseOrderList($data['order_id']);

            http_response_code(200);
            echo json_encode($order_list);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}