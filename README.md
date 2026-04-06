# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

## Features

- Fast and accurate language detection
- Support for 59 languages
- IPA-based transliteration system
- Data sourced from Wiktionary using MediaWiki APIs
- Kubernetes-based deployment

## Supported Languages

|            |              |            |                 |
|------------|--------------|------------|-----------------|
| Afar       | Afrikaans    | Albanian   | Arabic          |
| Armenian   | Azerbaijani  | Bengali    | Breton          |
| Bulgarian  | Burmese      | Catalan    | Czech           |
| Danish     | Dutch        | English    | Estonian        |
| Finnish    | French       | Galician   | Georgian        |
| German     | Greek        | Gullah     | Hausa           |
| Hebrew     | Hindi        | Hungarian  | Icelandic       |
| Italian    | Japanese     | Kazakh     | Komi            |
| Korean     | Latin        | Latvian    | Lithuanian      |
| Mandarin   | Middle Dutch | Mongolian  | Norwegian       |
| Old Dutch  | Pali         | Persian    | Polish          |
| Portuguese | Romanian     | Russian    | Serbo-Croatian* |
| Somali     | Spanish      | Swahili    | Swedish         |
| Tagalog    | Telugu       | Turkish    | Ukrainian       |
| Urdu       | Uzbek        | Vietnamese |                 |

*Officially deprecated. Serbo-Croatian here includes: Bosnian, Croatian, Montenegrin, Serbian.

## Data Sources

IPA data for universal transliteration is parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) using 
[MediaWiki API](https://www.mediawiki.org/wiki/API)

Note: for some languages used Wiktionary in it's respective language, e.g. https://nl.wiktionary.org/w/api.php for Dutch.

Implementations:
- [WiktionaryArticlesCategoriesService](src/Service/WiktionaryArticlesCategoriesService.php)
- [WiktionaryArticlesIpaParserService](src/Service/WiktionaryArticlesIpaParserService.php)

For a complete list of all source articles organized by language, see **[WORD_LISTS.md](WORD_LISTS.md)**.

## Requirements

- k3s cluster (1 server node + agent nodes)
- kubectl
- Docker (on the machine used to build and push images)
## Cluster Setup

### Install k3s

**Prerequisites:**
edit /boot/firmware/cmdline.txt
Add to the end of the existing line (do not add a new line):

cgroup_memory=1 cgroup_enable=memory
                  
It should look something like:
console=serial0,115200 console=tty1 root=PARTUUID=... rootfstype=ext4 fsck.repair=yes rootwait cgroup_memory=1 cgroup_enable=memory

**On the server node (first Pi):**
```bash
curl -sfL https://get.k3s.io | sh -
# Get the node token for joining agents
sudo cat /var/lib/rancher/k3s/server/node-token
```

**On each agent node (remaining Pis):**
```bash
curl -sfL https://get.k3s.io | K3S_URL=https://<server-ip>:6443 K3S_TOKEN=<node-token> sh -
```

**Copy kubeconfig to your workstation:**
```bash
scp <server-ip>:/etc/rancher/k3s/k3s.yaml ~/.kube/config
# Replace 127.0.0.1 with the server Pi's IP
sudo sed -i 's/127.0.0.1/192.168.0.197/' ~/.kube/config/k3s.yaml
```

### Configure the local registry on each node

Allow k3s to pull from the local insecure registry. Run on **every Pi**:

```bash
sudo mkdir -p /etc/rancher/k3s
sudo tee /etc/rancher/k3s/registries.yaml > /dev/null <<EOF
mirrors:
  "registry.local:30500":
    endpoint:
      - "http://<server-ip>:30500"
EOF
sudo systemctl restart k3s      # server node
# or
sudo systemctl restart k3s-agent  # agent nodes
```

Also add the registry hostname to `/etc/hosts` on every Pi and on any machine used to build images:

```bash
echo "<server-ip> registry.local" | sudo tee -a /etc/hosts
```

### Install Longhorn (distributed storage)

Longhorn provides persistent volumes replicated across nodes, so data survives node failures. Required for Elasticsearch indexes and generated docs.

**Prerequisites — run on every node:**
```bash
sudo apt-get install -y open-iscsi
sudo systemctl enable --now iscsid
```

**Install Longhorn:**
```bash
kubectl apply -f https://raw.githubusercontent.com/longhorn/longhorn/v1.7.2/deploy/longhorn.yaml
kubectl -n longhorn-system rollout status deploy/longhorn-manager
```

**Verify all pods are running:**
```bash
kubectl get pods -n longhorn-system
```

> If the `longhorn-system` namespace gets stuck in `Terminating` after a delete, force-remove its finalizer:
> ```bash
> kubectl get namespace longhorn-system -o json | python3 -c "import sys,json; d=json.load(sys.stdin); d=d['items'][0] if d.get('kind')=='List' else d; d['spec']['finalizers']=[]; print(json.dumps(d))" | kubectl replace --raw /api/v1/namespaces/longhorn-system/finalize -f -
> ```

## Installation & Setup

### 1. Configure secrets

Edit `k8s/secret.yaml` and fill in real values for all credentials before applying.

### 2. Start the registry

The registry must be running before you can push images to it:

```bash
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/registry.yaml
kubectl rollout status -n lingwhaat deployment/registry
```

### 3. Build and push images

Run on the machine used for building (must have Docker and `registry.local` in `/etc/hosts`):

```bash
docker build -t registry.local:30500/lingwhaat-php:latest -f Dockerfile-php .
docker push registry.local:30500/lingwhaat-php:latest
```
If you are getting error:
failed to do request: Head "https://registry.local:30500/v2/lingwhaat-php/blobs/sha256:b66328bafebb08ae7289fb693d3675d30ca74311ce321f3e8cb3701d1f7ed5b2": http: server gave HTTP response to HTTPS client

do:
sudo nano /etc/docker/daemon.json

Add:
{
  "insecure-registries": ["registry.local:30500"]
}

Then restart Docker and retry:

sudo systemctl restart docker
docker push registry.local:30500/lingwhaat-php:latest

### 4. Apply remaining manifests

```bash
kubectl apply -f k8s/
```

### 5. Run database migrations

```bash
kubectl exec -n lingwhaat deploy/web -- php bin/console doctrine:migrations:migrate --no-interaction
```

## Deploying updates

### 1. Build the image on the k3s node (or locally if node is reachable)
docker build -f Dockerfile-php -t registry.local:30500/lingwhaat-php:latest .

### 2. Push to the in-cluster registry
docker push registry.local:30500/lingwhaat-php:latest

### 3. Force a rollout (pulls the new :latest image)
kubectl rollout restart deployment/web -n lingwhaat

### 4. Watch until ready
kubectl rollout status deployment/web -n lingwhaat

If you also have new migrations:

### 5. Run migrations after the pod is up
kubectl exec -n lingwhaat deploy/web -- php bin/console doctrine:migrations:migrate --no-interaction

Note: registry.local must resolve to the node's IP. If you're running the build on a separate machine, either add registry.local to /etc/hosts       
pointing at the k3s node, or substitute the node's IP directly (e.g. 192.168.x.x:30500).

## Secrets

### Add a secret
```bash
kubectl patch secret lingwhaat-secrets -n lingwhaat --type='merge' -p '{"stringData":{"API_KEY":"xxx"}}'
```

### List (decode) secrets
```bash
kubectl get secret lingwhaat-secrets -n lingwhaat -o json | python3 -c "import sys,json,base64; d=json.load(sys.stdin)['data']; [print(f'{k}={base64.b64decode(v).decode()}') for k,v in d.items()]"
```

## Services

| Service | Type | Port |
|---|---|---|
| web (HTTP) | NodePort | 30080 |
| web (HTTPS) | NodePort | 30443 |
| redis | ClusterIP | 6379 |
| elasticsearch | NodePort | 30920 |
| kibana | NodePort | 30561 |
| argocd | NodePort | 30808 |

Database is hosted on AWS RDS and accessed via `DATABASE_URL` in `k8s/secret.yaml`.

Elasticsearch and Kibana run in the `lingwhaat` namespace and are accessible internally via `elasticsearch:9200`.

## ArgoCD

### Install

```bash
kubectl create namespace argocd
kubectl apply -n argocd -f https://raw.githubusercontent.com/argoproj/argo-cd/stable/manifests/install.yaml
kubectl wait --for=condition=available --timeout=180s -n argocd deployment/argocd-server
kubectl apply -f k8s/argocd-nodeport.yaml
```

### Access

Open `https://<pi-ip>:30808` in your browser and accept the self-signed certificate warning.

- **Username:** `admin`
- **Password:** retrieve with:

```bash
kubectl -n argocd get secret argocd-initial-admin-secret -o jsonpath="{.data.password}" | base64 -d
```

Change the password after first login via the ArgoCD UI under **User Info → Update Password**.

## Kubernetes Dashboard

### Install

```bash
kubectl apply -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.7.0/aio/deploy/recommended.yaml
kubectl create serviceaccount dashboard-admin -n kubernetes-dashboard
kubectl create clusterrolebinding dashboard-admin --clusterrolebinding=cluster-admin --serviceaccount=kubernetes-dashboard:dashboard-admin
```

### Access

```bash
kubectl -n kubernetes-dashboard create token dashboard-admin
kubectl port-forward -n kubernetes-dashboard svc/kubernetes-dashboard 8443:443 --address 0.0.0.0
```

Open `https://<pi-ip>:8443`, accept the self-signed certificate warning, and paste the token.

## Troubleshooting

**Redeploy after code changes:**
```bash
docker build -t registry.local:30500/lingwhaat-php:latest -f Dockerfile-php .
docker push registry.local:30500/lingwhaat-php:latest
kubectl apply -f k8s/configmap.yaml
kubectl rollout restart -n lingwhaat deployment/web deployment/messenger deployment/messenger-wiktionary deployment/messenger-wikipedia deployment/scheduler
```

**Check pod status:**
```bash
kubectl get pods -n lingwhaat
kubectl logs -n lingwhaat -l app=web --tail=30
```