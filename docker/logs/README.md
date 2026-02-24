# Docker Logs

This directory contains logs from Docker containers:
- `nginx/` - Nginx access and error logs
- `mysql/` - MySQL error logs

Logs are preserved on the host machine even if containers are removed.

## .gitignore

Add to `.gitignore`:
```
docker/logs/**
!docker/logs/**/.gitkeep
```
