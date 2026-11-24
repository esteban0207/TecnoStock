
# TecnoStock

TecnoStock es un sistema de gestión de inventario desarrollado como proyecto universitario para la asignatura de Sistemas de Información.

El objetivo del proyecto es facilitar el control de productos de una tienda de tecnología, permitiendo llevar un registro de las existencias y de los movimientos de entrada y salida de mercadería desde una misma plataforma.

## Funcionalidades

- Inicio y cierre de sesión.
- Panel principal con información del inventario.
- Registro y edición de productos.
- Clasificación de productos por categorías.
- Activación y desactivación de productos.
- Registro de entradas y salidas de stock.
- Control de stock disponible y stock mínimo.
- Historial de movimientos con filtros y paginación.
- Validación de datos ingresados en los formularios.
- Modo claro y oscuro.

## Tecnologías utilizadas

- HTML5 y CSS3
- JavaScript
- PHP 8
- MySQL
- Bootstrap 5.3
- PDO para la conexión con la base de datos
- XAMPP como entorno de desarrollo local

## Instalación

Para ejecutar el proyecto de forma local se necesita tener XAMPP instalado.

1. Descargar o clonar este repositorio.
2. Copiar la carpeta del proyecto dentro de `htdocs` de XAMPP, con el nombre `TecnoStock`.
3. Iniciar Apache y MySQL desde el panel de control de XAMPP.
4. Ingresar a phpMyAdmin desde http://localhost/phpmyadmin/.
5. Crear la base de datos e importar el archivo SQL del proyecto.
6. Revisar el archivo `conexion.php` y comprobar que los datos de conexión coincidan con la configuración de MySQL.
7. Abrir el navegador e ingresar a http://localhost/TecnoStock/login.php.

Para iniciar sesión se debe utilizar una cuenta registrada en la base de datos.

## Estructura del proyecto

```text
TecnoStock/
├── css/
├── js/
├── conexion.php
├── login.php
├── logout.php
├── dashboard.php
├── productos.php
├── crear_producto.php
├── editar_producto.php
├── cambiar_estado.php
├── movimientos.php
├── guardar_movimiento.php
├── historial.php
└── README.md
```

## Funcionamiento del sistema

Una vez iniciada la sesión, el usuario puede acceder al panel principal, consultar los productos registrados y realizar movimientos de inventario.

Cada entrada aumenta el stock del producto y cada salida lo disminuye. El sistema comprueba que exista stock suficiente antes de registrar una salida, evitando que el inventario quede con cantidades negativas.

Los movimientos quedan registrados en un historial que permite consultar las operaciones realizadas anteriormente.

## Seguridad y validaciones

El sistema utiliza sesiones para controlar el acceso, consultas preparadas mediante PDO y protección CSRF en las operaciones correspondientes. También cuenta con validaciones en JavaScript y PHP para comprobar los datos ingresados.

## Sobre el proyecto

TecnoStock fue desarrollado con fines académicos, aplicando los conocimientos adquiridos durante la asignatura de Sistemas de Información. Se buscó crear un sistema funcional, fácil de utilizar y que respondiera a una necesidad real de control de inventario.
