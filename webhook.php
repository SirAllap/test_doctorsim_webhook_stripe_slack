<?php

// Configuración de Stripe y Slack
define('SLACK_WEBHOOK_URL', 'https://hooks.slack.com/services/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');

require 'vendor/autoload.php';

// Función para manejar el webhook de Stripe
function handleStripeWebhook($payload)
{
    $event = $payload['type'];

    switch ($event) {
        case 'payment_intent.succeeded':
        case 'charge.succeeded':
            handlePaymentDispute($payload['data']['object']);
            break;

        default:
            http_response_code(400);
            die('Evento no manejado');
    }
}

// Función para manejar una disputa de pago
function handlePaymentDispute($dispute)
{
    $amount = number_format($dispute['amount'] / 100, 2);

    // Enviar notificación a Slack
    $cardBrand = $dispute['payment_method_details']['card']['brand'];
    sendSlackNotification(sprintf(
        "Disputa recibida de %s por valor de %s EUR. Pago realizado con tarjeta %s",
        $dispute['customer'],
        $amount,
        $cardBrand
    ));

    // Buscar pedidos asociados a este cliente
    $customerOrders = searchCustomerOrders($dispute['customer']);
    if (!empty($customerOrders)) {
        // Enviar información de los pedidos a Slack
        sendSlackNotification("Pedidos asociados al cliente {$dispute['customer']}: " . implode(', ', $customerOrders));
    } else {
        // Enviar notificación a Slack
        sendSlackNotification("Este cliente no tiene más pedidos asociados");
    }
}

// Función para buscar pedidos asociados a un cliente en Stripe
function searchCustomerOrders($customerId)
{
    $customerOrders = [];
    try {
        \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

        $customer = \Stripe\Customer::retrieve($customerId);

        // Recorrer las "facturas" del cliente y extraer detalles del pedido
        foreach ($customer->invoices->data as $invoice) {
            foreach ($invoice->lines->data as $lineItem) {
                // Extraer detalles del pedido
                $orderDetails = [
                    'order_id' => $lineItem->id,
                    'amount' => $lineItem->amount,
                    'currency' => $lineItem->currency,
                ];

                $customerOrders[] = $orderDetails;
            }
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Error en la API de Stripe: " . $e->getMessage());
    }

    // Devolver el array de pedidos del cliente
    return $customerOrders;
}

// Función para enviar notificaciones a Slack
function sendSlackNotification($message)
{
    $payload = json_encode(['text' => $message]);

    $ch = curl_init(SLACK_WEBHOOK_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $result = curl_exec($ch);
    curl_close($ch);
}

// Manejar el webhook de Stripe cuando se recibe una solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = @file_get_contents('php://input');
    $event = json_decode($payload, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        die('Carga no válida');
    }

    handleStripeWebhook($event);
} else {
    http_response_code(405);
    die('Método no permitido');
}
