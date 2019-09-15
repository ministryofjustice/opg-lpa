#! /bin/sh

export TERRAFORM_VERSION=0.12.5
export TERRAFORM_SHA256SUM=babb4a30b399fb6fc87a6aa7435371721310c2e2102a95a763ef2c979ab06ce2
curl https://releases.hashicorp.com/terraform/${TERRAFORM_VERSION}/terraform_${TERRAFORM_VERSION}_linux_amd64.zip > terraform_${TERRAFORM_VERSION}_linux_amd64.zip
echo "${TERRAFORM_SHA256SUM}  terraform_${TERRAFORM_VERSION}_linux_amd64.zip" > terraform_${TERRAFORM_VERSION}_SHA256SUMS
sha256sum -c -s terraform_${TERRAFORM_VERSION}_SHA256SUMS
unzip terraform_${TERRAFORM_VERSION}_linux_amd64.zip -d /bin
rm -f terraform_${TERRAFORM_VERSION}_linux_amd64.zip
terraform -version