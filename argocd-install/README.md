# ArgoCD installation tuning

These manifests configure the ArgoCD installation itself (namespace `argocd`), as opposed to
`k8s/`, which is the `lingwhaat` application that ArgoCD deploys. **Nothing in this directory is
synced by the `lingwhaat` Application** (`k8s/argocd-app.yaml` only watches the `k8s/` path) —
apply changes here manually:

```bash
kubectl apply -f argocd-install/
```

## Why this exists

This repo's `.git` history is ~1.16GB (`https://api.github.com/repos/akabihno/lingwhaat` reports
`size: 1164120` KB), far larger than the current working tree, almost certainly from large binary
blobs committed at some point and never purged from history. `argocd-repo-server` does a full
(non-shallow) `git fetch --tags --force --prune` to sync, which at this cluster's home-network
upload/download speed to GitHub (observed ~0.6-1.5 MB/s, fluctuating) took 15-25+ minutes for a
full clone. Two defaults made this fail outright:

- `argocd-repo-server`'s per-git-command exec timeout (`ARGOCD_EXEC_TIMEOUT`, default 90s) and
  `argocd-application-controller`'s per-RPC deadline to repo-server
  (`controller.repo.server.timeout.seconds`, default 60s) were both far shorter than a full clone
  needs.
- The repo-server's git clone cache lives under `/tmp`, which was an `emptyDir` — wiped on every
  pod restart, forcing a fresh multi-GB-equivalent full clone every time the pod moved or crashed.

## What's here

- `repo-server-tmp-pvc.yaml` — a Longhorn-backed PVC for `argocd-repo-server`'s `/tmp`, so a
  completed clone survives pod restarts and future syncs only need a small incremental
  `git fetch`. Requires `fsGroup: 999` on the pod (see below) since the repo-server container runs
  as uid/gid 999 (`argocd`) and a fresh PVC mount defaults to root ownership.

## Manual patches applied (not yet expressed as manifests here)

Applied directly with `kubectl patch` / `kubectl edit` — reproduce these if the ArgoCD install is
ever redeployed from scratch:

- `argocd-cmd-params-cm` (namespace `argocd`): `controller.repo.server.timeout.seconds: "1800"`
- `argocd-repo-server` Deployment: env `ARGOCD_EXEC_TIMEOUT=1800s`
- `argocd-repo-server` Deployment: `spec.template.spec.securityContext.fsGroup: 999`
- `argocd-repo-server` Deployment: `tmp` volume changed from `emptyDir: {}` to
  `persistentVolumeClaim: {claimName: argocd-repo-server-tmp}`

## If the repo history bloat is ever addressed

If the large historical blobs are ever stripped (e.g. via `git filter-repo` + a coordinated
force-push), the timeouts above can likely be lowered back toward the ArgoCD defaults, and the
PVC becomes optional rather than load-bearing.
