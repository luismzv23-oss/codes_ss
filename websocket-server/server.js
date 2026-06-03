const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", // En producción, restringir al dominio de CodeIgniter
        methods: ["GET", "POST"]
    }
});

io.on('connection', (socket) => {
    console.log('Cliente conectado:', socket.id);

    // Si quieres permitir que un cliente se suscriba a eventos específicos
    socket.on('subscribe_event', (eventId) => {
        socket.join('event_' + eventId);
        console.log(`Socket ${socket.id} suscrito a event_${eventId}`);
    });

    socket.on('disconnect', () => {
        console.log('Cliente desconectado:', socket.id);
    });
});

// Endpoint para recibir el Broadcast desde PHP (Webhook interno)
app.post('/broadcast', (req, res) => {
    const { event_id, odd_id, new_value, status, old_value } = req.body;

    if (!odd_id || !new_value) {
        return res.status(400).json({ error: 'Faltan parámetros.' });
    }

    console.log(`[BROADCAST] Evento: ${event_id} | Odd: ${odd_id} | Valor: ${old_value} -> ${new_value}`);

    // Emitir a todos los clientes (o solo a los suscritos al evento si quisieras)
    io.emit('odd_update', {
        event_id: event_id,
        odd_id: odd_id,
        new_value: new_value,
        old_value: old_value,
        status: status
    });

    res.json({ success: true, message: 'Broadcast emitido' });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`WebSocket Server (Socket.io) corriendo en el puerto ${PORT}`);
});
