# Supervisor Examples

## Purpose

The PHP orchestrator is the inner queue and state machine. Long-running execution should be handled by an outer supervisor such as a shell loop, `systemd`, or `cron`.

## Files

- [`run-worker-loop.sh`](~/git/ec-cube-alps/orchestrator/run-worker-loop.sh): minimal shell supervisor
- [`orchestrator-worker.service.example`](~/git/ec-cube-alps/orchestrator/orchestrator-worker.service.example): `systemd` service example
- [`orchestrator-worker.timer.example`](~/git/ec-cube-alps/orchestrator/orchestrator-worker.timer.example): `systemd` timer example
- [`orchestrator-worker.crontab.example`](~/git/ec-cube-alps/orchestrator/orchestrator-worker.crontab.example): cron example

## Shell Loop

```bash
PROJECT_ROOT=~/git/ec-cube-alps \
SLEEP_SECONDS=5 \
bash orchestrator/run-worker-loop.sh
```

The script writes logs to `.migrate/logs/worker-loop.log` and keeps calling `php bin/orchestrator worker once`.

## systemd

Copy the example files, replace `~/git/ec-cube-alps`, then enable them.

```bash
systemctl --user daemon-reload
systemctl --user enable --now orchestrator-worker.service
```

Use the timer example if you prefer periodic polling instead of a long-lived loop.

## cron

Install the crontab example after replacing the project path.

```bash
crontab orchestrator/orchestrator-worker.crontab.example
```
