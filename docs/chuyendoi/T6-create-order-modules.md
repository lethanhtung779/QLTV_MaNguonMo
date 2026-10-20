# Task T6: Tạo `OrderController`, `OrderRepository`, `OrderService`

**Mục cible:** Xây dựng các module quản lý đơn hàng (tạo đơn, cập nhật trạng thái, hủy đơn, trừ/hoàn lại tồn kho).

## Các thay đổi chi tiết

### 1. `OrderRepository`

| Phương thức | Mô tả |
|-------------|-------|
| `all($search, $status, $limit, $offset)` | Lấy danh sách đơn hàng có phân trang |
| `find($id)` | Lấy chi tiết đơn hàng theo ID |
| `findByCustomer($customerId)` | Lấy đơn hàng của khách hàng |
| `create($data)` | Tạo đơn hàng mới |
| `updateStatus($id, $status)` | Cập nhật trạng thái đơn hàng |
| `cancel($id)` | Hủy đơn hàng |
| `count($search, $status)` | Đếm số đơn hàng |

### 2. `OrderService`

| Phương thức | Mô tả |
|-------------|-------|
| `placeOrder($customerId, $cartItems, $paymentMethod)` | Tạo đơn hàng, trừ tồn kho, gửi email |
| `updateOrderStatus($orderId, $status, $actorId)` | Cập nhật trạng thái, hoàn/lưu tồn kho nếu cần |
| `cancelOrder($orderId, $actorId, $reason)` | Hủy đơn hàng, hoàn lại tồn kho |
| `getOrderWithItems($orderId)` | Lấy đơn hàng kèm chi tiết |

### 3. `OrderController`

| Route | Phương thức | Action | Mô tả |
|-------|-------------|--------|-------|
| `/admin/orders` | GET | `index` | Danh sách đơn hàng |
| `/admin/orders/{id}` | GET | `show` | Chi tiết đơn hàng |
| `/admin/orders/{id}/status` | POST | `updateStatus` | Cập nhật trạng thái (nhân viên) |
| `/admin/orders/{id}/cancel` | POST | `cancel` | Hủy đơn hàng |
| `/checkout` | POST | `store` | Tạo đơn hàng từ giỏ (khách hàng) |
| `/order/success` | GET | `success` | Trang thành công |

### 4. Luồng trừ/högeri tồn kho

- Khi `placeOrder`: trừ `stock` của từng sách trong đơn
- Khi `cancelOrder`: hoàn lại `stock` nếu đơn chưa giao
- Khi `updateStatus` sang `cancelled`: hoàn lại `stock`

## Definition of Done

- [ ] Tạo 3 file: `OrderController.php`, `OrderRepository.php`, `OrderService.php`
- [ ] Logic trừ tồn kho khi đặt hàng
- [ ] Logic hoàn lại tồn kho khi hủy đơn
- [ ] Gửi email thông báo khi đơn hàng được tạo
- [ ] Commit và push lên nhánh `feature/convert-to-bookstore`
