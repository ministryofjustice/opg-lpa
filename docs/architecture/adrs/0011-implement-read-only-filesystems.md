# 0011. Refactor container images to use read-only root filesystems

Date: 16/11/2022

## Status

Accepted: 16/11/2022

## Context

Disabling write access to the root file system ensures that we are following best container practices as documented by AWS. ECS_CONTAINERS_READONLY_ACCESS will flag as non-compliant if root file systems are not set to read only whilst containers running on ECS.

This will make it difficult for an attacker to make application changes if they are able to gain access through remote code execution or remote shell on any of our application containers.

## Final Proposal

`readonlyRootFilesystem` will be set to true in all application container definitions. This will prevent the container from being able to write to `/` and subdirectories.

The exception to this will be `/tmp`, which all containers will have R+W access to. This, however, prevents the application code from being changed during runtime.

In a similar vein as to Kuberetes' [InitContainers](https://kubernetes.io/docs/concepts/workloads/pods/init-containers/), an initial container will be started as part of each task which will run a chmod command against the `app_tmp` volume. This will change the permissions from the default of `755` to `766` as the former would not allow `appuser` to write to it. Once this is completed, the initContainer will exit and the volume will be mounted as `tmp` in the main app container.

## Consequences

- Although we don't use it, ECS Exec will no longer be usable in ECS.
- Manual UAT should be performed in case there are any gaps in testing
