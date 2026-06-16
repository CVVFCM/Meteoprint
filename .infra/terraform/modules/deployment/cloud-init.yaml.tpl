#cloud-config

manage_resolv_conf: true
resolv_conf:
  nameservers:
    - '2001:4860:4860::6464' # DNS64 Google
    - '2a00:1098:2c::1'     # DNS64 nat64.xyz

write_files:
  - path: /etc/systemd/network/50-cloud-init-eth0.network.d/dns64.conf
    content: |
      [Network]
      DNS=2001:4860:4860::6464
      DNS=2a00:1098:2c::1
  - path: /etc/apt/sources.list.d/docker.sources
    content: |
      Types: deb
      URIs: https://download.docker.com/linux/debian
      Suites: trixie
      Components: stable
      Architectures: amd64
      Signed-By: /etc/apt/keyrings/docker.asc

runcmd:
    - systemctl restart systemd-networkd

    - apt-get install -y ca-certificates curl
    - install -m 0755 -d /etc/apt/keyrings
    - curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
    - chmod a+r /etc/apt/keyrings/docker.asc
    - apt-get update
    - apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

    - curl -fsSL https://tailscale.com/install.sh | sh
    - tailscale up --auth-key=${tailscale_key} --ssh

users:
  - name: ${ssh_user}
    ssh_authorized_keys:
      - ${deploy_public_key}
      - ${github_keys}
    groups: [docker]
