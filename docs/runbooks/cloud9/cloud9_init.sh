#! /bin/bash
function main() {
  install_tools
  infer_account ${1:?}
  add_rds_sgs ${1:?}
  postgresql ${1:?}
  setup_info
}

function install_tools() {
  sudo amazon-linux-extras install postgresql10 jq -y
  sudo yum install jq -y
}

function infer_account() {
  case "${1:?}" in
  'preproduction')
  export ACCOUNT="${1:?}"
  ;;
  'production')
  export ACCOUNT="${1:?}"
  ;;
  *)
  export ACCOUNT="development"
  ;;
  esac
}

function add_rds_sgs() {
  local current_sg_name
  local current_sg_id
  local environment_name=${1:?}
  local api_rds_client_sg
  local caseworker_rds_client_sg
  local CURRENT_SG_IDS=()

  echo "getting current sg names and instance id..."
  current_sg_names=$(curl -s http://169.254.169.254/latest/meta-data/security-groups)
  instance_id=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)

  echo "getting app rds sg id..."
  api_rds_client_sg=$(
    aws ec2 describe-security-groups \
    --filters Name=group-name,Values="rds-client-${environment_name}" \
    --query "SecurityGroups[0].GroupId" \
    --output text
  )

  if [[ $current_sg_names =~ "rds-client-${environment_name}" ]]; then
    echo "Security Groups already attached..."
    return
  fi

  echo "getting current (cloud9) sg id..."
  current_sg_id=$(
    aws ec2 describe-security-groups \
    --filters Name=group-name,Values=$current_sg_names \
    --query "SecurityGroups[0].GroupId" \
    --output text
  )

  echo "modifying cloud9..."
  aws ec2 modify-instance-attribute --groups "${current_sg_id}" "${api_rds_client_sg}" --instance-id ${instance_id}
}

function postgresql() {
  local environment_name=$(echo ${1:?} | awk '{print tolower($0)}')
  local db_instance=$(aws rds describe-db-instances --db-instance-identifier api-${environment_name})

  export AWS_DEFAULT_REGION=eu-west-1
  export PGHOST=$( jq -r .'DBInstances'[0].'Endpoint'.'Address' <<< "${db_instance}")
  export DB_INSTANCE=$( jq -r .'DBInstances'[0].'DBInstanceIdentifier' <<< "${db_instance}")
  export DB_NAME=$( jq -r .'DBInstances'[0].'DBName' <<< "${db_instance}")
  export PGUSER=$(aws secretsmanager get-secret-value --secret-id ${ACCOUNT}/api_rds_username | jq -r .'SecretString')
  export PGPASSWORD=$(aws secretsmanager get-secret-value --secret-id ${ACCOUNT}/api_rds_password | jq -r .'SecretString')
}

function setup_info() {
  echo ""
  echo "------------------------------------------------------------------------------------"
  echo "Postgres host is $PGHOST"
  echo "------------------------------------------------------------------------------------"
  echo "api PostgreSQL Instance set to $DB_INSTANCE"
  echo "------------------------------------------------------------------------------------"
  echo "type psql $DB_NAME to connect"
  echo "------------------------------------------------------------------------------------"
  echo ""
}

main ${1:?}
