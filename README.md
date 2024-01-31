# Webhook de Stripe y Notificaciones a Slack

Este proyecto maneja eventos de webhook de Stripe y envía notificaciones a un canal de Slack correspondiente.

## Configuración

1. **Configuración de Stripe:**
   - Obtén tu clave secreta de Stripe desde el [panel de control de Stripe](https://dashboard.stripe.com/apikeys) y actualiza la constante `STRIPE_SECRET_KEY` en `.env`.

2. **Configuración de Slack:**
   - Crea un webhook en tu espacio de trabajo de Slack y actualiza la constante `SLACK_WEBHOOK_URL` en `webhook.php`.

3. **Instalación de Dependencias:**
   - Ejecuta `composer install` para instalar las dependencias necesarias.

## Ejecución

1. **Configuración del Servidor Web:**
   - Initia tu servidor: `php -S localhost:8000`.

