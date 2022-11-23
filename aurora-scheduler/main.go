package main

import (
	"context"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/rds"

	"fmt"
)

func StartDBClusters(ctx context.Context) (string, error) {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		panic("configuration error, " + err.Error())
	}

	client := rds.NewFromConfig(cfg)

	input := &rds.DescribeDBClustersInput{}

	result, err := client.DescribeDBClusters(ctx, input)
	if err != nil {
		panic("failed to describe db clusters, " + err.Error())
	}

	for _, d := range result.DBClusters {
		if *d.EngineMode == "provisioned" {
			// TODO: Add logic to start only the clusters that are stopped
			fmt.Printf("Starting %s", *d.DBClusterIdentifier)
			_, err := client.StartDBCluster(context.TODO(), &rds.StartDBClusterInput{
				DBClusterIdentifier: d.DBClusterArn,
			})
			if err != nil {
				fmt.Println(err.Error())
			} else {
				fmt.Printf("Started %s", *d.DBClusterIdentifier)
			}
		}
	}
	return "Success", nil
}

func StopDBClusters(ctx context.Context) (string, error) {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		panic("configuration error, " + err.Error())
	}

	client := rds.NewFromConfig(cfg)

	input := &rds.DescribeDBClustersInput{}

	result, err := client.DescribeDBClusters(ctx, input)
	if err != nil {
		panic("failed to describe db clusters, " + err.Error())
	}

	for _, d := range result.DBClusters {
		if *d.EngineMode == "provisioned" {
			// TODO: Add logic to stop only the clusters that are running
			fmt.Printf("Stopping %s", *d.DBClusterIdentifier)
			_, err := client.StopDBCluster(context.TODO(), &rds.StopDBClusterInput{
				DBClusterIdentifier: d.DBClusterArn,
			})
			if err != nil {
				fmt.Println(err.Error())
			} else {
				fmt.Printf("Stopped %s", *d.DBClusterIdentifier)
			}
		}
	}
	return "Success", nil
}

type cmdEvent struct {
	Command string `json:"command"`
}

func HandleRequest(ctx context.Context, command cmdEvent) (string, error) {
	if command.Command == "start" {
		StartDBClusters(context.TODO())
	} else {
		StopDBClusters(context.TODO())
	}

	return "Success", nil
}

func main() {
	lambda.Start(HandleRequest)
}
