# # data "aws_route53_zone" "lastingpowerofattorney_service_gov_uk" {
# #   # provider = aws.opg-lpa-prod
# #   name = "lastingpowerofattorney.service.gov.uk"
# # }


# # data source for production04 load balancer
# data "aws_elb" "old_production04_front" {
#   name = "front2-production04"
# }
# output "old_production04_front" {
#   value = data.aws_elb.old_production04_front
# }
# # data source for maintenance cloudfront dist

# # data source for new production application load balancer
# data "aws_lb" "new_lpa_production_front" {
#   provider = aws.new_lpa_prod_ecs
#   # arn  = "${var.lb_arn}"
#   name = "production-front"
# }

# output "new_lpa_production_front" {
#   value = data.aws_lb.new_lpa_production_front
# }

# output "cloudfront_distribution_maintenance" {
#   value = aws_cloudfront_distribution.maintenance
# }


# # resource "aws_route53_record" "lastingpowerofattorney_service_gov_uk" {
# #   zone_id = "${data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.zone_id}"
# #   name    = "lastingpowerofattorney.service.gov.uk"
# #   type    = "A"

# #   alias {
# #     evaluate_target_health = false
# #     name                   = "${aws_lb.old_production04_front.dns_name}"
# #     zone_id                = "${aws_lb.old_production04_front.zone_id}"
# #     name                   = "${aws_lb.aws_cloudfront_distribution.maintenance.domain_name}"
# #     zone_id                = "${aws_lb.aws_cloudfront_distribution.maintenance.hosted_zone_id}"
# #     name                   = "${aws_lb.new_lpa_production_front.dns_name}"
# #     zone_id                = "${aws_lb.new_lpa_production_front.zone_id}"
# #   }

# #   lifecycle {
# #     create_before_destroy = true
# #   }
# # }

# # output "live_service_url" {
# #   value = aws_route53_record.lastingpowerofattorney_service_gov_uk
# # }

# resource "aws_route53_record" "maintenance_lastingpowerofattorney_service_gov_uk" {
#   zone_id = "${data.aws_route53_zone.lastingpowerofattorney_service_gov_uk.zone_id}"
#   name    = "maintenance.lastingpowerofattorney.service.gov.uk"
#   type    = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = "${data.aws_elb.old_production04_front.dns_name}"
#     zone_id                = "${data.aws_elb.old_production04_front.zone_id}"
#     # name                   = "${aws_cloudfront_distribution.maintenance.domain_name}"
#     # zone_id                = "${aws_cloudfront_distribution.maintenance.hosted_zone_id}"
#     # name                   = "${data.aws_lb.new_lpa_production_front.dns_name}"
#     # zone_id                = "${data.aws_lb.new_lpa_production_front.zone_id}"
#   }

#   lifecycle {
#     create_before_destroy = true
#   }
# }

# output "maintenance_service_url" {
#   value = aws_route53_record.maintenance_lastingpowerofattorney_service_gov_uk
# }
