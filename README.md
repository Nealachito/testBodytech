# Email Processing System – Backend

Sistema backend desarrollado en PHP (Laravel) para el procesamiento masivo de correos electrónicos desde archivo .xlsx, validación estructural, consulta HTTP externa y almacenamiento de resultados.

---

## 🚀 Tecnologías Utilizadas

- PHP 8+
- Laravel 10+
- SqlLite
- Laravel Queue
- Laravel HTTP Client
- maatwebsite/excel (para lectura de xlsx)
- Composer

---

## 📌 Funcionalidad

El sistema permite:

- Cargar un archivo .xlsx con N correos electrónicos
- Validar la estructura de cada correo
- Consultar un servicio HTTP externo por cada correo válido (Esto es mockeado en realidad se valida el email con una función de php)
- Almacenar resultados en base de datos
- Procesar los registros de manera asíncrona
- Actualizar progreso en tiempo real
- Generar resumen dinámico por código HTTP

---

## 🏗 Arquitectura

- **file_uploads** → Registro principal por archivo cargado
- **emails** → Registro individual por cada correo procesado
- **Job (ProcessFileUpload)** → Procesamiento asíncrono
- **Queue Worker** → Manejo de procesamiento prolongado
- **HTTP Pool** → Optimización de llamadas externas

---

## 📂 Instalación

### 1️⃣ Clonar repositorio

git clone <https://github.com/Nealachito/testBodytech.git>
cd email-processor


### 2️⃣ Instalar dependencias
composer install

### 3️⃣ Configurar entorno

Copiar archivo .env:

cp .env.example .env

Configurar base de datos:

DB_DATABASE=emails_db
DB_USERNAME=root
DB_PASSWORD=

### 4️⃣ Generar key
php artisan key:generate

### 5️⃣ Ejecutar migraciones
php artisan migrate

### 6️⃣ Configurar Queue

En .env:

QUEUE_CONNECTION=database


Crear tabla de jobs:

php artisan queue:table
php artisan migrate

### 7️⃣ Ejecutar servidor
php artisan serve

### 8️⃣ Ejecutar worker
php artisan queue:work

---

🔄 Flujo de Procesamiento

1. Usuario carga archivo
2. Se crea registro en file_uploads
3. Se despacha Job a Queue
4. Job:
    *Lee archivo
    *Valida estructura
    *Ejecuta requests en paralelo
    *Guarda resultados
    *Actualiza progreso
    *Frontend consulta estado hasta completarse
---
⚡ Manejo de Procesamiento Prolongado

Uso de Queue

Uso de HTTP Pool

Actualización por batches

Polling desde frontend

---

🧠 Decisiones Técnicas

Separación clara de responsabilidades

Persistencia incremental

Optimización de requests externos

Manejo de errores con timeout y retry

---

📌 Consideraciones

No se implementó autenticación (según requerimientos)

No se valida existencia real del correo

El sistema procesa todos los registros
