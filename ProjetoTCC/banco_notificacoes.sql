-- Patas & Lar — banco para as notificações do dono do animal
USE patas_lar;

-- resposta do dono do animal a cada pedido: Pendente, Aceita ou Recusada
-- (se der "Duplicate column name", a coluna já existe: pode ignorar)
ALTER TABLE adocao ADD COLUMN status_dono VARCHAR(20) NOT NULL DEFAULT 'Pendente';x 

-- permite excluir o animal quando a adoção é aceita, mantendo o histórico do pedido
-- (o pedido fica com id_animal vazio e aparece como "Animal removido" no painel do admin)
ALTER TABLE adocao DROP FOREIGN KEY fk_adocao_animal;
ALTER TABLE adocao MODIFY id_animal INT NULL;
ALTER TABLE adocao ADD CONSTRAINT fk_adocao_animal
  FOREIGN KEY (id_animal) REFERENCES animal(id_animal) ON DELETE SET NULL;
