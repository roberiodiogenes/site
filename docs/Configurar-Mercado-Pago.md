# Passo 1 — Obter suas credenciais de teste no Mercado Pago

Acesse mercadopago.com.br/developers
Faça login com sua conta Mercado Pago
Clique em "Suas integrações" → "Criar aplicação" (ou use uma existente)
No menu da aplicação, vá em "Credenciais" → aba "Credenciais de teste"
Copie o Access Token (começa com TEST-) e a Public Key (começa com TEST-)


# Passo 2 — Colar no backend/pagamento.php
Abra o arquivo e substitua as linhas 24 e 28:
php// ANTES:
? 'TEST-COLE_SEU_ACCESS_TOKEN_DE_TESTE_AQUI'
? 'TEST-COLE_SUA_PUBLIC_KEY_DE_TESTE_AQUI'

// DEPOIS (exemplo com formato real):
? 'TEST-1234567890abcdef1234567890abcdef-12345678'
? 'TEST-abcdef1234567890abcdef1234567890-12345678'

## Como testar após configurar
Use o cartão de teste do Mercado Pago — ele nunca cobra de verdade:
CampoValorNúmero5031 4332 1540 6351Validade11/25CVV123NomeQualquer nomeCPF12345678909
Com o token correto, o fluxo completo vai funcionar: clica em Comprar → abre checkout MP → paga com cartão de teste → webhook atualiza o banco → acesso liberado no leitor.
Quando for publicar o site na Hostgator, basta trocar os tokens de teste pelos de produção (que ficam na aba "Credenciais de produção" do mesmo painel).