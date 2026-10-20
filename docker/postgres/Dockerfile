FROM postgres:18

COPY docker/postgres/initdb /docker-entrypoint-initdb.d
COPY docker/postgres/postgresql.conf /etc/postgresql/postgresql.conf

EXPOSE 5432
