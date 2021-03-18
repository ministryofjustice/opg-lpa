#!/bin/bash
# Stitch together PF feature files 
cp cypress/integration/LpaTypePF.feature cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CreatePFLpa.feature >> cypress/integration/StitchedCreatePFLpa.feature 
# Stitch together HW feature files 
cp cypress/integration/LpaTypeHW.feature cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CreateHWLpa.feature >> cypress/integration/StitchedCreateHWLpa.feature 
