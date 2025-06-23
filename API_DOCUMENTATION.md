# API Documentation - LestJam

## Base URL
```
http://localhost:8006/api
```

## Autenticação

### Login
**POST** `/login`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "email": "user@email.com",
    "password": "password123"
}
```

**Resposta de Sucesso (200):**
```json
{
    "user": {
        "id": 1,
        "name": "Usuário Teste",
        "email": "user@email.com"
    },
    "token": "1|Au4n3gtBscC77IrxUj8OlyyC1eVQc6JKyFoDyCxE6324c4fd"
}
```

**Resposta de Erro (401):**
```json
{
    "message": "Invalid credentials"
}
```

**Resposta de Validação (422):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

## Usuários de Teste Disponíveis

| Email | Senha | Nome |
|-------|-------|------|
| user@email.com | password123 | Usuário Teste |
| joao@email.com | password123 | João Silva |
| maria@email.com | password123 | Maria Santos |

## Exemplos de Uso

### JavaScript/Fetch
```javascript
const loginUser = async (email, password) => {
    try {
        const response = await fetch('http://localhost:8006/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            // Login bem-sucedido
            console.log('Token:', data.token);
            console.log('Usuário:', data.user);
            return data;
        } else {
            // Erro de login
            console.error('Erro:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
};

// Exemplo de uso
loginUser('user@email.com', 'password123')
    .then(data => {
        // Salvar token no localStorage
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
    })
    .catch(error => {
        console.error('Falha no login:', error);
    });
```

### Axios
```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8006/api'
});

const loginUser = async (email, password) => {
    try {
        const response = await api.post('/login', {
            email: email,
            password: password
        });
        
        return response.data;
    } catch (error) {
        if (error.response) {
            throw new Error(error.response.data.message);
        }
        throw error;
    }
};
```

### cURL
```bash
curl -X POST http://localhost:8006/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@email.com","password":"password123"}'
```

## Scripts Úteis

### Executar Seeder de Usuários de Teste
```bash
./seed-test-users.sh
```

### Executar Todos os Testes
```bash
./vendor/bin/phpunit
```

### Executar Testes Específicos
```bash
# Apenas testes de feature (API)
./vendor/bin/phpunit --testsuite=Feature

# Apenas testes unitários
./vendor/bin/phpunit --testsuite=Unit

# Apenas testes de integração
./vendor/bin/phpunit --testsuite=Integration
``` 