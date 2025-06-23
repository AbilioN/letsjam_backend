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

### Registro
**POST** `/register`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "name": "João Silva",
    "email": "joao@email.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Resposta de Sucesso (201):**
```json
{
    "user": {
        "id": 2,
        "name": "João Silva",
        "email": "joao@email.com"
    },
    "token": "2|Bv5o4huCtdD88JsxVk9PmzzD2fWRd7KLzGpEzDyF7435d5ee"
}
```

**Resposta de Validação (422):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["O nome é obrigatório."],
        "email": ["O email deve ser válido."],
        "password": ["A confirmação da senha não confere."]
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

### JavaScript/Fetch - Login
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
```

### JavaScript/Fetch - Registro
```javascript
const registerUser = async (name, email, password, passwordConfirmation) => {
    try {
        const response = await fetch('http://localhost:8006/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                password_confirmation: passwordConfirmation
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            // Registro bem-sucedido
            console.log('Token:', data.token);
            console.log('Usuário:', data.user);
            return data;
        } else {
            // Erro de registro
            console.error('Erro:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
};
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

const registerUser = async (name, email, password, passwordConfirmation) => {
    try {
        const response = await api.post('/register', {
            name: name,
            email: email,
            password: password,
            password_confirmation: passwordConfirmation
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

### cURL - Login
```bash
curl -X POST http://localhost:8006/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@email.com","password":"password123"}'
```

### cURL - Registro
```bash
curl -X POST http://localhost:8006/api/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"João Silva","email":"joao@email.com","password":"password123","password_confirmation":"password123"}'
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