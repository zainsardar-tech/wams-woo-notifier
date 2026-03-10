# Use Cases for WAMS Woo Notifier

WAMS Woo Notifier is designed to streamline communication between store owners and customers. Here are several common use cases that demonstrate how the plugin can add value to your WooCommerce store.

## 1. Real-Time Order Confirmation
When a customer completes a purchase, receiving an immediate confirmation via WhatsApp provides reassurance that their order was successful.
*   **Trigger:** Order Status changed to "Processing" or "Completed".
*   **Benefit:** Reduces customer anxiety and decreases the number of "Did my order go through?" support inquiries.
*   **Template Example:** `Hello {customer_name}, thank you for your order #{order_id}! We have received your payment and are now preparing your items.`

## 2. Shipping & Tracking Updates
Keeping customers informed about their shipment is crucial for a positive post-purchase experience.
*   **Trigger:** Order Status changed to "Shipped" or "Completed" (depending on your fulfillment flow).
*   **Benefit:** Improves transparency and allows customers to prepare for delivery.
*   **Template Example:** `Great news {customer_name}! Your order #{order_id} has been shipped. You can track it here: {tracking_link}.`

## 3. Payment Reminders for BACS/Bank Transfers
If you offer offline payment methods like Bank Transfer, customers often forget to complete the transaction.
*   **Trigger:** Order Status changed to "On Hold".
*   **Benefit:** Increases conversion rates for offline payments by providing a gentle, direct reminder.
*   **Template Example:** `Hi {customer_name}, your order #{order_id} is currently on hold awaiting payment. Please let us know once you've made the transfer.`

## 4. Personalized Customer Engagement
WhatsApp is a more personal channel than email. Using it for order updates makes your brand feel more accessible.
*   **Trigger:** Any status change.
*   **Benefit:** Builds stronger customer relationships and brand loyalty.
*   **Template Example:** `Hey {customer_name}, we've just finished processing your order #{order_id}. We hope you love your new products!`

## 5. Reducing Email Fatigue
Many customers' email inboxes are cluttered with promotions. WhatsApp notifications ensure that important transactional information doesn't get lost in the noise.
*   **Benefit:** Higher open rates compared to traditional email notifications.

## 6. Admin Notifications (Internal)
*(Note: If configured)* Store managers can receive notifications on their own WhatsApp when new orders are placed.
*   **Trigger:** New Order Created.
*   **Benefit:** Allows the operations team to react instantly to new sales without constantly checking the WordPress dashboard.

---

For more information on setup and configuration, refer to the [README.md](README.md) or visit [wams.aztify.com](https://wams.aztify.com).
