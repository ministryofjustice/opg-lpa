#!/usr/bin/env bash


function get_alb_rule_arn() {
  MM_ALB_ARN=$(aws elbv2 describe-load-balancers --names  "${ENVIRONMENT}-front" | jq -r .[][]."LoadBalancerArn")
  MM_LISTENER_ARN=$(aws elbv2 describe-listeners --load-balancer-arn ${MM_ALB_ARN} | jq -r '.[][]  | select(.Protocol == "HTTPS") | .ListenerArn')
  MM_RULE_ARN=$(aws elbv2 describe-rules --listener-arn ${MM_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "101") | .RuleArn')
}

function enable_maintenance() {
  MM_DNS_PREFIX="${ENVIRONMENT}."
  if [ $ENVIRONMENT = "production" ]
  then
    MM_DNS_PREFIX="www."
  fi
 aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=host-header,Values="${MM_DNS_PREFIX}lastingpowerofattorney.service.gov.uk"
}

function disable_maintenance() {
 aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=path-pattern,Values='/maintenance'
}

function parse_args() {
  for arg in "$@"
  do
      case $arg in
          -e|--environment)
          ENVIRONMENT=$(echo "$2" | tr '[:upper:]' '[:lower:]')
          shift
          shift
          ;;
          -m|--maintenance_mode)
          MAINTENANCE_MODE=True
          shift
          ;;
          -d|--disable_maintenance_mode)
          MAINTENANCE_MODE=False
          shift
          ;;
      esac
  done
}

function start() {
  get_alb_rule_arn
  if [ $MAINTENANCE_MODE = "True" ]
  then
    enable_maintenance
  else
    disable_maintenance
  fi
}

MAINTENANCE_MODE=False
parse_args $@
start
