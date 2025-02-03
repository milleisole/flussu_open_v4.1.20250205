#!/bin/bash
echo "Installing Flussu cron job:"
# Determina dinamicamente il percorso dello script in base alla directory corrente
WORK_DIR="$(pwd)"
SCRIPT="$WORK_DIR/flussu.sh"
chmod +x "$SCRIPT"
TEMP_CRON=$(mktemp)
sudo crontab -l 2>/dev/null > "$TEMP_CRON"
# Il cron job cambia la directory corrente in WORK_DIR e poi esegue flussu.sh
CRON_LINE="*/1 * * * * cd $WORK_DIR && $SCRIPT"
# Verifica se la linea esiste già
if ! grep -Fq "$CRON_LINE" "$TEMP_CRON"; then
    # Aggiunge il comando se non esiste
    echo "$CRON_LINE" >> "$TEMP_CRON"
    # Installa il nuovo crontab per l'utente root
    sudo crontab "$TEMP_CRON"
    echo "  - Flussu cron job added."
else
    echo "  - Flussu cron job already installed."
fi
# Rimuove il file temporaneo
rm "$TEMP_CRON"