var express = require('express'),
    app = express(),
    bodyParser = require('body-parser');

app.use(bodyParser({limit: '50mb'}));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded());

/** Allow access to retrieve resources
 * 
 */
    app.use(express.static('uploads'));
    app.use(express.static('web'));

/** Web Sockets
 * 
 */
    var WebSocket = require('ws');
    var wss = new WebSocket.Server({ port: 9998, path:'/task' });
    var clients = [];

    wss.on('connection', function connection(ws){
        console.log('connected ws');
        ws.on('message', function incoming(message){
            try {
                console.log("incoming message: " + message);

                //Convertir a JSON
                const data = JSON.parse(message);

                //Checar tipo de conexión, si es nueva conexión o mensaje nuevo ó desconexión
                switch(data.accion) {
                    case "conectar": //Si es nueva conexión, agregar cliente
                        var objCliente = { "client": ws, "room": data.room, "id_usuario": data.id_usuario };
                        var clientExists = false;

                        //verificar si dicho cliente ya está conectado y remplazar su conexión
                        clients.forEach(function each(client){
                            if(client.id_usuario == objCliente.id_usuario) {
                                client.client = ws;
                                client.room = data.room;
                                clientExists = true;
                            }
                        });

                        //Si el cliente no estaba en el array, agregarlo
                        if(!clientExists) {
                            clients.push(objCliente);
                        }
                        
                        console.log("online clients:" + clients.length);
                        break;
                    case "desconectar": //Si es desconexión, desconectar
                        break;
                    case "typing":
                        enviarATodos("typing",clients, data, ws);
                        break;                    
                    case "conectado":
                    case "enviar": //Si es nuevo mensaje, enviar a todos
                        enviarATodos("enviar",clients, data, ws);
                        break;   
                    default:
                        return;
                }
                          
            } catch(err) {
                console.log(err.message);
            }
        })
    });

    function enviarATodos(accion,clients, data, ws){
        clients.forEach(function each(client){
            console.log(client.id_usuario);
            if(client.client != ws && client.room === data.room && client.client.readyState === WebSocket.OPEN){
                data.datos.accion = accion;
                console.log("sending: " + JSON.stringify(data.datos));
                client.client.send(JSON.stringify(data.datos)); 
            }
        })         
    }