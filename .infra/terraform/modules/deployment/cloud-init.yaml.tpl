#cloud-config

write_files:
  - path: /etc/sudoers.d/debian-nopasswd
    permissions: '0440'
    content: |
      debian ALL=(ALL) NOPASSWD:ALL
  - path: /etc/apt/sources.list.d/docker.sources
    content: |
      Types: deb
      URIs: https://download.docker.com/linux/debian
      Suites: trixie
      Components: stable
      Architectures: amd64
      Signed-By: /etc/apt/keyrings/docker.asc

runcmd:
    - apt-get install -y ca-certificates curl
    - install -m 0755 -d /etc/apt/keyrings
    - curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
    - chmod a+r /etc/apt/keyrings/docker.asc
    - apt-get update
    - apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

    - curl -fsSL https://tailscale.com/install.sh | sh
    - tailscale up --auth-key=${tailscale_key} --ssh --accept-dns=true
    - systemctl disable --now systemd-resolved
    - rm /etc/resolv.conf
    - systemctl restart tailscaled

users:
  - name: ${ssh_user}
    ssh_authorized_keys:
      - ${deploy_public_key}
      - ${github_keys}
    groups: [docker]
