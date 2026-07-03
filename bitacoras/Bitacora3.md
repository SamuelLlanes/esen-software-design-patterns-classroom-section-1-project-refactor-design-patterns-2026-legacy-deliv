# Bitácora · Mini-entrega 3 — Singleton / Observer

**Nombre:** Carlos Samuel Llanes Cornejo
**Fecha:** 07-06-2026
**Mini-entrega:** 3
**Módulo refactorizado:** Notificaciones / Logger / Observer

---

## 1. ¿Qué problema de diseño identifiqué en el legacy?

Primero, en app/Models/Customer.php el metodo placeOrder() 
mezcla la creacion de la orden con notificaciones directas
a servicios fijos:

```php
$emailService = new \App\Services\EmailService();
$smsService   = new \App\Services\SMSService();
$emailService->send($this->user->email, 'Pedido confirmado', ...);
```

Aca se viola la responsabilidad unica porque Customer sabe demasiado 
sobre que canales usar y cuando mandar las alertas. El problema real 
es que si cambia el flujo de notificaciones hay que tocar el modelo 
de dominio, haciendo un dolor de cabeza extender o probar el comportamiento.

Segundo, en app/Models/Order.php el metodo notify() tambien armaba los 
servicios de notificacion directamente y llamaba a Logger::getInstance() 
al final. Ese Logger es un singleton global en app/Support/Logger.php 
que mete un acoplamiento implicito horrible y hace que todo dependa de 
un estado compartido.

```php
\App\Support\Logger::getInstance()->log("Order {$this->id} event dispatched: {$event}");
```

La consecuencia real es que el codigo es fragil a cambios: los logs quedan atrapados en 
un singleton global y no podes cambiar las notificaciones sin romper el modelo.

---

## 2. ¿Qué patrón aplicaste y por qué resuelve este problema?

Aplique el patron Observer para separar el evento de la orden del 
envio de notificaciones. El pedido (Order) ahora es un sujeto que 
dispara el evento y el despachador (OrderNotificationDispatcher) 
avisa a los observadores.

Participantes:
- `OrderNotificationDispatcher` — Subject / Observable
- `OrderObserver` — interfaz Observer
- `EmailOrderObserver` — observador concreto para correo
- `SmsOrderObserver` — observador concreto para SMS
- `PushOrderObserver` — observador concreto para Push
- `LoggerOrderObserver` — observador concreto para logs


Tambien meti inyeccion de dependencias para el logger, borrando 
el singleton de app/Support/Logger.php y registrandolo en el 
container de Laravel. Esto quita el estado global y deja la 
dependencia explicita.

Clases nuevas o modificadas:
- `app/Services/Notifications/OrderObserver.php` — interfaz Observer
- `app/Services/Notifications/OrderNotificationDispatcher.php` — Subject que despacha a los observers
- `app/Services/Notifications/EmailOrderObserver.php` — envía emails
- `app/Services/Notifications/SmsOrderObserver.php` — envía SMS
- `app/Services/Notifications/PushOrderObserver.php` — envía push
- `app/Services/Notifications/LoggerOrderObserver.php` — registra evento en logger
- `app/Models/Order.php` — usa `notify()` con dispatcher y `app(Logger::class)`
- `app/Models/Customer.php` — reemplaza notificaciones hardcodeadas por `$order->notify('created')`
- `app/Support/Logger.php` — elimina `getInstance()` y convierte el logger en servicio inyectable
- `app/Providers/AppServiceProvider.php` — registra `Logger` y `OrderNotificationDispatcher`

---

## 3. ¿Qué patrón descartaste y por qué?

Para las notificaciones descarte usar Strategy. Sirve si ocupas 
elegir entre algoritmos de calculo distintos, pero aca el problema 
era repartir un evento a varios canales a la vez. Strategy me 
habria dejado una clase rigida por cada notificacion sin 
separar bien la generacion del evento de la entrega a los observadores.

Tambien descarte dejar el logger como singleton. Aunque parece comodo, 
te mete un estado global oculto que acopla el codigo. La inyeccion de 
dependencias es mejor aca porque deja la dependencia a la vista y ayuda 
a aislar las cosas.

---

## 4. ¿Qué trade-off aceptaste?

Acepte meter mas clases y archivos para tener las responsabilidades bien 
separadas. Agregue 6 clases nuevas para el Observer y modifique otras 6 
existentes para meter el dispatcher y el logger inyectado.

El costo es que ahora hay mas archivos que mantener y el flujo de notificacion 
no esta centralizado en un solo metodo. Puede costar entenderlo al principio 
para alguien nuevo, pero gano modularidad para meter mas canales luego 
sin tocar el modelo.

---

## 5. ¿Qué cambiarías si tuvieras que hacerlo de nuevo?

No usaria app(...) dentro de Order::notify(), mejor moveria la creacion 
del dispatcher a un servicio dedicado inyectado por el constructor para 
no resolverlo desde el modelo. Tambien sacaria el save() de 
transitionTo() para dejar la persistencia como un paso separado y 
explicito en lugar de guardar inmediatamente en la base de datos al cambiar 
de estado.
