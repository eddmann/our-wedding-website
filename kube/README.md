# Running on a K3s Raspberry Pi Cluster

These are notes taken from my experience getting the application to run on a Kubernetes (K3s) Raspberry Pi Cluster over a long bank-holiday weekend.
The aim was to not alter the subject application, but instead only apply additive changes found in here.

## Boot the Cluster

- Download the [Raspberry Pi Imager](https://www.raspberrypi.com/software/).
- Select Raspberry Pi OS Lite (64-bit, Debian Bullseye), update the hostname/SSH-credentials, and flash the micro-SD card.
- Boot up the Pi.
- Copy your SSH key to the Pi, by running `ssh-copy-id pi@HOSTNAME.local`.
- _[On the Pi]_ Update the static IP defined within `/etc/dhcpcd.conf` by adding.
  ```
  interface eth0
  static ip_address={PI_IP}/24
  static routers=192.168.1.1
  static domain_name_servers=192.168.1.1
  ```
- _[On the Pi]_ Add the following to the end of `/boot/cmdline.txt`, to enable container functionality.
  `cgroup_enable=cpuset cgroup_memory=1 cgroup_enable=memory`
- _[On the Pi]_ Reboot the Pi.
- Download the [k3sup tool](https://github.com/alexellis/k3sup) and install the k3s server on the desired Pi by running.
  `k3sup install --ip {SERVER_IP} --user pi`
- Once installed, follow the above steps to flash and configure the agent Pi's.
  However, instead of installing the server, we will join the existing cluster by running.
  `k3sup join --ip {AGENT_IP} --server-ip {SERVER_IP} --user pi`

## Set up the Cluster

Build and push the application image, ensuring we target the architecture compatible with the Pi.

```
cp docker/.dockerignore ../.dockerignore
docker buildx create --use
docker buildx build \
    --platform linux/amd64,linux/arm64 \
    -t ghcr.io/eddmann/our-wedding-website-kube:latest \
    -f docker/Dockerfile \
    --target prod \
    --push \
    ../
```

Create the namespace we wish to put the application within.

```
apiVersion: v1
kind: Namespace
metadata:
    name: our-wedding-website
```

Add the credentials used to authenticate with the GitHub Container Registry.

```
kubectl create secret docker-registry github \
    --namespace our-wedding-website \
    --docker-server=https://ghcr.io \
    --docker-username=irrelevant-user \
    --docker-password=#PAT#
```

Install _cert-manager_ used to handle SSL certificates.

```
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.8.0/cert-manager.yaml
```

Add staging and production LetsEncrypt issuers.

```
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-staging
  namespace: our-wedding-website
spec:
  acme:
    email: user@email.com
    server: https://acme-staging-v02.api.letsencrypt.org/directory
    privateKeySecretRef:
      name: staging-issuer-account-key
    solvers:
    - http01:
        ingress:
          class: traefik
```

```
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
  namespace: our-wedding-website
spec:
  acme:
    email: user@email.com
    server: https://acme-v02.api.letsencrypt.org/directory
    privateKeySecretRef:
      name: prod-issuer-account-key
    solvers:
    - http01:
        ingress:
          class: traefik
```

Deploy (or upgrade if already installed) the application.

```
helm upgrade our-wedding-website ./helm --install -n our-wedding-website -f ./helm/values.yaml -f ./helm/secrets.yaml
```

Access and interact with the deployed application.

```
export KUBECONFIG={k3sup supplied config}
kubectl config set-context --current --namespace=our-wedding-website
kubectl port-forward svc/web 8081:80
kubectl exec -it svc/web -- sh
kubectl exec -it svc/postgres -- sh
kubectl scale --replicas=2 deployment/web
kubectl scale --replicas=2 deployment/worker
```

Optionally, if you wish to access the website from outside your local network you can configure an exit-node on a VPS (such as EC2).
Due to the low port numbers we wish to bind to you must ensure you can log in as _root_.
This is conventionally a big no-no but for limited experimentation you can include the following in your `/etc/ssh/sshd_config`.

```
GatewayPorts yes
PermitRootLogin yes
```

Finally, you can select one of the Pi nodes and run the following.

```
ssh -R80:127.0.0.1:80 -R443:127.0.0.1:443 -N root@EXIT_NODE_IP -i {KEY} &
```

This will bind the Pi's local port 80/443 to the exit-nodes port 80/443, and run in the background.
In doing so we do not need to open up our local network to gain access from the public internet.
