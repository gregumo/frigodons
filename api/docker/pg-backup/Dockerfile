FROM postgres:18.3

RUN \
  apt-get update && \
  apt-get install -y cron s3cmd  && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# Copy the scripts
COPY ./docker/pg-backup/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY ./docker/pg-backup/run-backups.sh /usr/local/bin/run-backups.sh
RUN chmod +x /usr/local/bin/run-backups.sh

COPY ./docker/pg-backup/prune-backups.sh /usr/local/bin/prune-backups.sh
RUN chmod +x /usr/local/bin/prune-backups.sh

# Run backups hourly and prune old backups daily at 00:30.
RUN echo "0 * * * * /usr/local/bin/run-backups.sh >> /var/log/cron.log 2>&1" > /etc/cron.d/pg_backup
RUN echo "30 0 * * * /usr/local/bin/prune-backups.sh >> /var/log/cron.log 2>&1" >> /etc/cron.d/pg_backup
RUN chmod 0644 /etc/cron.d/pg_backup

RUN crontab /etc/cron.d/pg_backup

ENTRYPOINT [ "entrypoint.sh" ]
