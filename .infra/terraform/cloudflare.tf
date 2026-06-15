provider "cloudflare" {
}

resource "cloudflare_dns_record" "dns" {
  zone_id = "3fa2035c4239d02756471c8a0f51f247"
  name    = "meteoprint.cvvfcm.fr"
  type    = "AAAA"
  content = scaleway_instance_ip.v6.address
  ttl     = 1
  proxied = true
}
