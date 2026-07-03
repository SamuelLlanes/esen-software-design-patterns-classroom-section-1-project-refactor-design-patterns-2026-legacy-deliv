# Bitácora · Mini-entrega 4 — Adapter y Facade

**Nombre:** Carlos Samuel Llanes Cornejo
**Mini-entrega:** 4
**Módulo refactorizado:** Pasarelas de pago (Adapter) y Checkout (Facade)

---

## 1. ¿Qué problema de diseño identifiqué en el legacy?

### Problema 1: Pasarelas de pago (Adapter)

En app/Services/Payments/, cada proveedor tiene métodos con nombres y parámetros distintos:



Principio violado: Liskov Substitution Principle (LSP). Los handlers no son intercambiables — cada uno expone métodos con nombres distintos.


---

### Problema 2: Checkout (Facade)

En app/Models/Customer.php, el método placeOrder() contiene toda la lógica del checkout en un solo método hace 8 responsabilidades simultáneamente:

public function placeOrder(array $cart, string $paymentMethod): Order
{
    // --- Validación del customer
    // --- Validación del vendor (debería estar en Vendor)
    // --- Validación de horarios
    // --- Construcción de items
    // --- Cálculo de subtotal
    // --- Aplicación de descuentos
    // --- Procesamiento de pagos
    // --- Creación de Order
}

Principio violado: Single Responsibility Principle (SRP). Un método no debería hacer 8 cosas distintas.

---

## 2. ¿Qué patrón aplicaste y por qué resuelve este problema?

### Patrón 1: Adapter (para pasarelas de pago)

Creé una interfaz común PaymentGatewayAdapter que define processPayment(), refund(), getTransactionStatus(). Ahora cada proveedor ahora tiene un adapter que traduce su interfaz específica al contrato común

Cada handler concreto ahora implementa este adapter:
 WompiPaymentAdapter — adapta los métodos cobrar(), reembolsar(), etc. de WompiHandler

 N1coPaymentAdapter — adapta procesarPago(), rocesarReembolso(), etc. de N1coHandler

 BacPaymentAdapter — adapta procesarTransferencia(), etc. de BacTransferHandler

Ahora el código cliente puede cambiar entre proveedores sin saber que existen interfaces distintas en el backend. El adapter "traduce" la interfaz específica de cada proveedor a un contrato común.

---

### Patrón 2: Facade (para checkout)

Creé `CheckoutFacade` que orquesta los subsistemas:

class CheckoutFacade {
    public function placeOrder(Customer $customer, array $cart, string $paymentMethod): Order
    {
        // Coordina:
        // 1. CustomerValidator::validate()
        // 2. VendorValidator::validate()
        // 3. CartProcessor::processItems()
        // 4. PricingCalculator::calculate()
        // 5. PaymentProcessor::process()
        // 6. Order::create() + OrderItems
    }
}

Creé subsistemas especializados:
CustomerValidator — valida cliente

VendorValidator — valida vendor y horarios
CartProcessor — valida y procesa items
PricingCalculator — calcula precios finales
PaymentProcessor — procesa pago usando el adaptador


Ahora la lógica compleja queda dentro de subsistemas testables. El cliente solo llama $facade->placeOrder() y obtiene una orden creada. Si cambio cómo se calculan precios, toco PricingCalculator, no la facade.

---

## Clases nuevas o modificadas:

Adapter:
app/Services/Payments/PaymentGatewayAdapter.php — interfaz común
app/Services/Payments/WompiPaymentAdapter.php — adaptador concreto
app/Services/Payments/N1coPaymentAdapter.php — adaptador concreto
app/Services/Payments/BacPaymentAdapter.php — adaptador concreto
app/Services/Payments/PaymentAdapterFactory.php — factory para crear adaptadores

Facade:
app/Services/Checkout/CheckoutFacade.php — facade principal
app/Services/Checkout/CustomerValidator.php — subsistema
app/Services/Checkout/VendorValidator.php — subsistema
app/Services/Checkout/CartProcessor.php — subsistema
app/Services/Checkout/PricingCalculator.php — subsistema
app/Services/Checkout/PaymentProcessor.php — subsistema (usa Adapter)

Modificados:
app/Models/Customer.php — placeOrder() delegará a CheckoutFacade

---

## 3. ¿Qué patrón descartaste y por qué?

### Patrón descartado: Strategy (para pagos)

Podría haber usado Strategy con una clase PaymentStrategy y execute(amount), pero eso no habría resuelto el problema real. Strategy es para variar algoritmos, pero acá el problema es que interfaces distintas necesitan ser traducidas a una interfaz común. Eso es exactamente lo que Adapter hace.

Strategy habría dejado el problema sin resolver: seguiría teniendo que saber si llamo cobrar() o procesarPago().

### Patrón descartado: Builder (para checkout)

Builder es útil cuando la construcción de un objeto es compleja y tiene muchos pasos opcionales. Pero acá:
 Todos los pasos son obligatorios (validar customer, vendor, procesar pago)

 El orden es fijo (no puedo pagar antes de validar vendor)

 No hay variación significativa en el proceso

Facade es mejor porque simplifica la coordinación de subsistemas que ya existen, sin añadir complejidad de construcción.

---

## 4. ¿Qué trade-off aceptaste?

Costos:
Agregué 10 clases nuevas (5 adapters + 5 subsistemas) en lugar de dejar todo centralizado en placeOrder()

El flujo de checkout es ahora menos obvio. Ahora necesitas entender que CheckoutFacade delega a 5 subsistemas. Un desarrollador nuevo tarda más en encontrar dónde se calcula el precio.

Ahora hay ás archivos para mantener. Si necesitas editar la lógica de validación, tienes que ir a CustomerValidator en lugar de estar todo en un método.

Hay un nivel más de indirección. Para entender qué pasa en processPayment(), necesitas entrar en CheckoutFacade -> PaymentProcessor -> PaymentAdapterFactory -> PaymentGatewayAdapter.

Beneficios:
Puedo testear PricingCalculator sin simular un pago

Puedo agregar un nuevo proveedor de pagos (ej. Stripe) creando StripePaymentAdapter sin tocar nada más

Puedo cambiar el algoritmo de cálculo de descuentos sin afectar la validación del customer

---
