#!/usr/bin/env sh

name_tag()
{
  branch=$1
  branch=${branch//-}
  branch=${branch//_}
  branch=${branch//\/}
  branch=${branch:0:13}
  branch=$(echo $branch | tr '[:upper:]' '[:lower:]')
  short_hash=${2:0:7}
  echo "$branch-$short_hash"
}

echo $(name_tag $1 $2)
