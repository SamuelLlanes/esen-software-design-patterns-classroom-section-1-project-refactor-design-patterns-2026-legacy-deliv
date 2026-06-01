# Bitácora · Mini-entrega N — [Título del patrón]

**Nombre:** Carlos Samuel Llanes Cornejo
**Fecha:** 29-5-2026
**Mini-entrega:** N
**Módulo refactorizado:** [ej. Descuentos / Notificaciones / Estados de Order]

---

## 1. ¿Qué problema de diseño identifiqué en el legacy?

En Discount.php, el metodo apply() acumulaba toda la logica. Lo que viola el principio de Single Responsibility y Open/Closed. En caso se mantuviera igual se corre el peligro de generar errores cuando se quisiera agregar un nuevo tipo de descuento.

<?php
// archivo: app/Models/Discount.php, línea ~30

Los tres archivos de la carpeta tenian la misma funcion de
validacion. Lo cual viola el principio Don't Reapeat
Yourself. El problema con violar este principio se ve a largo plazo, pues al tener que midificar un paso en la validacion
se tendria que realizar la misma edicion en varios archivos
diferentes.

<?php
// archivo: app/Reports/CsvReportGenerator.php, línea ~10

Tambien en Order.php  habia un gran problema con el array
$allowed y la validacion de las transiciones. Ahi se viola
Single Responsibility Principle y no se separan las
responsabilidades entre el estado y el comportamiento. El 
problema es que si queres meter o cambiar reglas de
transicion hay que modificar la clase Order arriesgando
romper otras cosas como validaciones o las notificaciones. 

<?php
// archivo: app/Models/Order.php, línea ~35

_Describe el problema concreto: archivo, método, línea aproximada. Explica qué principio viola y por qué eso causa un problema real — no en abstracto, sino en este sistema. "El código es difícil de leer" no aplica._

**Cita el fragmento del legacy que ilustra el problema** (2-5 líneas de código, con su archivo y número de línea):

```php
// archivo: app/Models/Ejemplo.php, línea XX
```

---

## 2. ¿Qué patrón aplicaste y por qué resuelve este problema?

En Discount.php el principio usado es Strategy. El problema que
resuleve es evitar que toda la logica se encuentre en una sola 
clase, dando una logica aislada a cada descuento evitando
problemas entre cada descuento.

Archivos crados o modificados:
app\Models\Discount.php — contexto Strategy, ahora delega el cálculo
app\Models\Discounts\DiscountStrategy.php — interfaz Strategy
app\Models\Discounts\BogoStrategy.php — estrategia concreta
app\Models\Discounts\DiscountStrategyFactory.php — resuelve qué estrategia usar
app\Models\Discounts\FirstPurchaseStrategy.php — estrategia concreta
app\Models\Discounts\FixedAmountStrategy.php— estrategia concreta
app\Models\Discounts\FreeDeliveryStrategy.php — estrategia concreta
app\Models\Discounts\PercentageStrategy.php — estrategia concreta

En los archivos de Reports/ el principio usado es Template Method.
El problema que resuelve es que centraliza el proceso que siguen
todos los generadores de reportes. Lo cual ahorra trabajo a
futuro a la hora de cambiar el proceso o agregar un nuevo
generador.
Archivos creados o modificados:
app\Reports\CsvReportGenerator.php — concrete template, formatea CSV
app\Reports\ExcelReportGenerator.php — concrete template, formatea Excel
app\Reports\PdfReportGenerator.php — concrete template, formatea PDF
app\Reports\ReportGenerator.php  — clase base Template Method

Para el problema de Order.php aplique State y Command juntos.
State, quita el array $allowed de Order y mete la logica de 
"desde X puedo ir a Y" en la clase de ese estado, haciendo la
entidad mas chica. Command, hace explicito el flujo y facilita meter pruebas o meter cosas como un proveedor de pagos en PayCommand sin tocar Order.

app\Models\Order — mantiene los datos, delega y ejecuta comandos

app\Models\OrderState — Interfaz del State

App\Models\States\PendingState.php — ConcreteState: estado created

App\Models\States\PaidState.php — ConcreteState: estado paid

App\Models\States\CancelledState.php — ConcreteState: estado cancelled

App\Models\States\DeliveredState.php — ConcreteState: estado delivered

App\Models\Commands\OrderCommand — Interfaz Command

App\Models\Commands\PayCommand — ConcreteCommand: comando para pagar (ejecuta transicion y efectos)

App\Models\Commands\CancelCommand — ConcreteCommand: comando para cancelar

_Conecta el patrón al problema específico de arriba. No copies la definición del libro — explica cómo los participantes del patrón (Context, Strategy, ConcreteStrategy, etc.) mapean a las clases que creaste o modificaste._

**Nombra las clases nuevas o modificadas en tu refactor:**

- `NombreClase` — rol que cumple en el patrón

---

## 3. ¿Qué patrón descartaste y por qué?

Para los reportes descarté Strategy. Serviría si los reportes
fueran totalmente distintos, pero acá el proceso tiene pasos 
fijos y solo cambia el formato. Template Method es mejor 
porque mantiene la estructura común y no crea clases de más. 
También descarté dejar solo una Factory en los descuentos; la 
Factory solo crea el objeto, pero si no metía Strategy el 
switch horrible solo se iba a mover de lugar sin arreglar 
nada.



_Nombra al menos un patrón alternativo. Explica por qué no resuelve el problema de la misma forma o genera un costo mayor en este contexto específico. "No lo conocía" no es razón válida._

---

## 4. ¿Qué trade-off aceptaste?

Mas clases y mas archvios accambio de mejor modularidad.
Para eso fueron creadas 8 clases, siendo complejo su
creacion y lectura. Ademas, agrega la complejidad a
futuro cuando se requiera editar algunas clases,
obligando al desarrollador tener que abrir varios
archivos en lugar de uno solo, sin embargo aumenta la
seguridad.

Tambien, modifique Order.php y agregue 8 clases mas.
Da mas complejidad operativa porque hay mas archivos que 
mantener y entender. Decidi que transitionTo() haga el save()
automaticamente para que no pase el bug real del legacy, 
pero esto mete un efecto lateral en la transicion por la 
escritura inmediata y puede sorprender en contextos 
transaccionales si el llamador queria controlar cuando
guardar. 

_Todo diseño tiene costos. Sé específico: ¿cuántas clases nuevas agregaste? ¿Qué se volvió más complejo? ¿Qué escenario futuro se hace más difícil con tu decisión? "El código quedó más limpio" no es un trade-off, es un beneficio._

---

## 5. ¿Qué cambiarías si tuvieras que hacerlo de nuevo?

Cambiaría el switch de Order::getState() por una StateFactory 
en el contenedor de Laravel para registrar nuevos estados sin 
tocar la clase Order. Además, sacaría el save() de 
transitionTo() porque meter la persistencia ahí mismo puede 
dar problemas en flujos transaccionales largos; es mejor 
dejar la actualización de la base de datos separada y 
explícita.

_Una oración. Puede ser algo que dejaste incompleto, una decisión que dudaste, o algo que descubriste tarde. Si no cambiarías nada, explica por qué estás seguro._
