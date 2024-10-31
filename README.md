=== NetPay Checkout ===

Contributors: NetPay
Tags: netpaymx, mexico, msi, cash, netpay, payment, payment gateway, woocommerce plugin, installment, woocommerce payment
Requires at least: 4.3.1
Tested up to: 6.4.3
Stable tag: 1.59.38
License: MIT
License URI: https://opensource.org/licenses/MIT

El plugin NetPay Checkout es la extensión de pago oficial que brinda soporte para la pasarela de pago NetPay para los constructores de tiendas que trabajan en la plataforma WooCommerce.

== Instalación ==

Después de obtener el plugin, ya sea descargándolo como un zip o clon de git, colóquelo en la carpeta de plugin de WordPress (es decir, mv netpay / wp-content / plugins / o cargue un zip a través de la sección de complementos de administración de WordPress, al igual que los otros complementos de WordPress).

Luego, el plugin de WordPress NetPay Checkout debería aparecer en la página de administración de WordPress, en el menú Plugins.
Desde allí:
1. Active el plugin
2. Vaya a WooCommerce -> Plugins
3. Seleccione la pestaña Pago en la parte superior.
4. Seleccione Pasarela de pago NetPay en la parte inferior de la página, en Pasarelas de pago.
5. Haga clic en el botón Configuración y ajuste las opciones.
6. Si utilizarás NetPay Cash, ve menú WooCommerce -> Ajustes -> Productos -> Investarios y coloca en el campo Mantener en inventario (en minutos) el valor de 14400, equivalente a 10 días que dura la referencia de pago, con el objetivo de que la órden no sea cancelada por falta de pago. (Si deseas cambiar el número de días en que expira la referencia de pago, ve a NetPay Manager).

== Documentación ==

Accesa [NetPay Docs](https://docs.netpay.com.mx/docs/woocommerce) para para conocer los pasos a seguir para implementar este plugin.

