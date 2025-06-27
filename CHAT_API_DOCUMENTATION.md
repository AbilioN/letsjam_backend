# Chat API Documentation

## Overview
Esta documentação descreve as rotas da API para o sistema de chat em tempo real entre admins e usuários.

## Base URL
```
http://localhost:8000/api
```

## Configuração de Broadcast

Para o chat funcionar em tempo real, configure as seguintes variáveis de ambiente:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_CLUSTER=your_pusher_cluster
```

## Endpoints

### 1. Enviar Mensagem (Usuário/Admin)
**POST** `/chat/send`

Envia uma mensagem para outro usuário ou admin.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "content": "Olá! Como posso ajudar?",
    "receiver_type": "admin",
    "receiver_id": 1
}
```

**Response (201):**
```json
{
    "message": {
        "id": 1,
        "content": "Olá! Como posso ajudar?",
        "sender_type": "user",
        "sender_id": 5,
        "receiver_type": "admin",
        "receiver_id": 1,
        "is_read": false,
        "created_at": "2025-06-27 21:30:00"
    }
}
```

### 2. Buscar Conversa (Usuário/Admin)
**GET** `/chat/conversation?other_user_type=admin&other_user_id=1&page=1&per_page=50`

Busca mensagens de uma conversa específica.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `other_user_type`: "user" ou "admin"
- `other_user_id`: ID do outro participante
- `page`: Página (opcional, padrão: 1)
- `per_page`: Itens por página (opcional, padrão: 50, máximo: 100)

**Response (200):**
```json
{
    "messages": [
        {
            "id": 1,
            "content": "Olá! Como posso ajudar?",
            "sender_type": "user",
            "sender_id": 5,
            "sender_name": "João Silva",
            "receiver_type": "admin",
            "receiver_id": 1,
            "is_read": false,
            "read_at": null,
            "created_at": "2025-06-27 21:30:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 50,
        "total": 1,
        "last_page": 1,
        "from": 1,
        "to": 1
    }
}
```

### 3. Listar Conversas (Usuário/Admin)
**GET** `/chat/conversations`

Lista todas as conversas do usuário/admin autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "conversations": [
        {
            "other_user_id": 1,
            "other_user_type": "admin",
            "last_message_at": "2025-06-27 21:30:00",
            "message_count": 5,
            "unread_count": 2
        }
    ]
}
```

## Admin Endpoints

### 4. Admin - Listar Conversas
**GET** `/admin/chat/conversations`

Lista todas as conversas do admin.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Response (200):**
```json
{
    "conversations": [
        {
            "other_user_id": 5,
            "other_user_type": "user",
            "last_message_at": "2025-06-27 21:30:00",
            "message_count": 5,
            "unread_count": 2
        }
    ]
}
```

### 5. Admin - Buscar Conversa com Usuário
**GET** `/admin/chat/conversation?user_id=5&page=1&per_page=50`

Busca conversa específica entre admin e usuário.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `user_id`: ID do usuário
- `page`: Página (opcional, padrão: 1)
- `per_page`: Itens por página (opcional, padrão: 50, máximo: 100)

### 6. Admin - Enviar Mensagem para Usuário
**POST** `/admin/chat/send`

Admin envia mensagem para um usuário específico.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Request Body:**
```json
{
    "content": "Olá! Sou o administrador. Como posso ajudar?",
    "user_id": 5
}
```

## WebSocket Events

### Evento: MessageSent
**Channel:** `chat.{user1_id}-{user2_id}` (canal privado)

**Payload:**
```json
{
    "message": {
        "id": 1,
        "content": "Olá! Como posso ajudar?",
        "sender_type": "user",
        "sender_id": 5,
        "sender_name": "João Silva",
        "receiver_type": "admin",
        "receiver_id": 1,
        "is_read": false,
        "created_at": "2025-06-27 21:30:00"
    }
}
```

## Implementação no Frontend

### Nuxt.js (Admin)
```javascript
// plugins/echo.js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.PUSHER_APP_KEY,
  cluster: process.env.PUSHER_APP_CLUSTER,
  forceTLS: true
})

// Componente de chat
export default {
  data() {
    return {
      messages: [],
      currentUser: null
    }
  },
  mounted() {
    this.listenToMessages()
  },
  methods: {
    listenToMessages() {
      const channelName = `chat.${this.currentUser.id}-${this.otherUserId}`
      
      window.Echo.private(channelName)
        .listen('MessageSent', (e) => {
          this.messages.push(e.message)
        })
    },
    
    async sendMessage(content) {
      await this.$axios.post('/api/admin/chat/send', {
        content,
        user_id: this.otherUserId
      })
    }
  }
}
```

### Flutter (Cliente)
```dart
// pubspec.yaml
dependencies:
  pusher_channels_flutter: ^2.0.0

// Chat Service
class ChatService {
  late PusherChannelsFlutter pusher;
  
  void initialize() {
    pusher = PusherChannelsFlutter.getInstance();
    pusher.init(
      apiKey: "YOUR_PUSHER_KEY",
      cluster: "YOUR_PUSHER_CLUSTER",
    );
  }
  
  void listenToMessages(int userId, int otherUserId, Function(Map<String, dynamic>) onMessage) {
    final channelName = 'chat.${userId < otherUserId ? userId : otherUserId}-${userId < otherUserId ? otherUserId : userId}';
    
    pusher.subscribe(
      channelName: channelName,
      onEvent: (event) {
        if (event.eventName == 'MessageSent') {
          final data = jsonDecode(event.data);
          onMessage(data['message']);
        }
      },
    );
  }
  
  Future<void> sendMessage(String content, String receiverType, int receiverId) async {
    await http.post(
      Uri.parse('$baseUrl/api/chat/send'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'content': content,
        'receiver_type': receiverType,
        'receiver_id': receiverId,
      }),
    );
  }
}
```

## Estrutura do Projeto

### Domain Layer
- `app/Domain/Entities/Message.php` - Entidade Message
- `app/Domain/Repositories/MessageRepositoryInterface.php` - Interface do repositório

### Application Layer
- `app/Application/UseCases/Chat/SendMessageUseCase.php` - Use case para enviar mensagem
- `app/Application/UseCases/Chat/GetConversationUseCase.php` - Use case para buscar conversa
- `app/Application/UseCases/Chat/GetConversationsUseCase.php` - Use case para listar conversas

### Infrastructure Layer
- `app/Infrastructure/Repositories/MessageRepository.php` - Implementação do repositório

### HTTP Layer
- `app/Http/Controllers/Api/Chat/ChatController.php` - Controller para usuários
- `app/Http/Controllers/Api/Admin/ChatController.php` - Controller para admins
- `app/Events/MessageSent.php` - Evento para broadcast

### Model
- `app/Models/Message.php` - Model Eloquent

## Funcionalidades

1. **Chat em tempo real** usando WebSockets
2. **Conversas privadas** entre usuários e admins
3. **Paginação** de mensagens
4. **Contagem de mensagens não lidas**
5. **Listagem de conversas** com última mensagem
6. **Broadcast automático** de mensagens
7. **Validação** de dados
8. **Autenticação** obrigatória

## Segurança

- Todas as rotas requerem autenticação
- Canais privados para conversas
- Validação de tipos de usuário (user/admin)
- Verificação de permissões de admin 