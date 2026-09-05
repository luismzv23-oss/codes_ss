# Guía Paso a Paso: Despliegue de Codex SS en Plataformas Cloud Administradas (PaaS)

Esta guía explica cómo publicar **Codex SS** en **Render / Railway** utilizando **MySQL Gestionado** y **Pusher (Serverless WebSockets)** sin necesidad de configurar ni mantener servidores Linux a mano.

---

## 1. Requisitos Previos (Gratuitos)

1. Una cuenta en **GitHub** ([github.com](https://github.com)).
2. Una cuenta en **Render** ([render.com](https://render.com)) o **Railway** ([railway.app](https://railway.app)).
3. *(Opcional)* Una cuenta en **Pusher** ([pusher.com](https://pusher.com)) para WebSockets sin servidor.

---

## 2. Despliegue en 1-Clic mediante Render

### Paso 1: Subir tu Código a GitHub
Sube la carpeta `codex_ss` a un repositorio privado o público en GitHub.

### Paso 2: Crear el Blueprint en Render
1. Inicia sesión en [render.com](https://render.com).
2. Haz clic en **New +** ➔ **Blueprints**.
3. Conecta tu repositorio de GitHub `codex_ss`.
4. Render detectará automáticamente el archivo [render.yaml](file:///c:/Users/luism/.gemini/antigravity/scratch/codex_ss/render.yaml).
5. Presiona **Apply**. Render creará de forma automática:
   - La base de datos **MySQL Gestionada** (`codex-ss-db`).
   - El servicio Web PHP **CodeIgniter 4** (`codex-ss-web`).
   - El servidor de WebSockets en Node.js (`codex-ss-websocket`).

---

## 3. Configuración Opcional: WebSockets Serverless con Pusher

Si prefieres eliminar el servidor de Node.js por completo y usar la infraestructura Serverless de **Pusher**:

1. Crea un canal en [pusher.com](https://pusher.com) y copia tus credenciales: `App ID`, `Key`, `Secret`, `Cluster`.
2. En las variables de entorno de tu aplicación en Render / Railway, añade:
   ```env
   PUSHER_APP_ID=tu_app_id
   PUSHER_KEY=tu_pusher_key
   PUSHER_SECRET=tu_pusher_secret
   PUSHER_CLUSTER=mt1
   ```
3. El sistema derivará automáticamente todos los broadcasts de cuotas en vivo a la red global de Pusher.

---

## 4. Ejecución de Migraciones de Base de Datos

Una vez publicado el servicio web, ejecuta la migración inicial visitando en tu navegador:
`https://tu-app-en-render.onrender.com/run-migrations-public`

*(Nota: Esta ruta responderá 403 Denegado si el entorno `CI_ENVIRONMENT` se cambia a `production` por seguridad).*

---

## 5. Ventajas de la Arquitectura Administrada

- **Cero Administración de Servidores**: Olvídate de parches del sistema operativo, SSL vencidos o reinicios de Nginx.
- **Auto-Despliegue**: Cada vez que hagas `git push` a la rama `main`, Render/Railway compilará y actualizará el sitio automáticamente sin tiempo de caída (*Zero-Downtime Deployment*).
