#!/bin/bash
# Stitch together PF feature files
cat cypress/e2e/LpaTypePF.feature | sed "s/@PartOfStitchedRun/@StitchedPF/" > cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/DonorPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/AttorneysPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ReusablePF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ReplacementAttorneysPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CertProviderPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/PeopleToNotifyPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/InstructionsPreferencesPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/SummaryPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ApplicantPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CorrespondentPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/WhoAreYouPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/RepeatApplicationPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/FeeReductionPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CheckoutPF.feature >> cypress/e2e/StitchedCreatePFLpa.feature
# Stitch together HW feature files
cat cypress/e2e/LpaTypeHW.feature | sed "s/@PartOfStitchedRun/@StitchedHW/" > cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/DonorHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/AttorneysHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ReusableHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ReplacementAttorneysHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CertProviderHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/PeopleToNotifyHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/InstructionsPreferencesHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/SummaryHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ApplicantHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CorrespondentHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/WhoAreYouHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/RepeatApplicationHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/FeeReductionHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CheckoutHW.feature >> cypress/e2e/StitchedCreateHWLpa.feature
# Stitch together PF Clone feature files
cat cypress/e2e/LpaTypePFClone.feature | sed "s/@PartOfStitchedRun/@StitchedClone/" > cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/DonorPF.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/AttorneysPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ReplacementAttorneysPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CertProviderPF.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/PeopleToNotifyPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/InstructionsPreferencesPF.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/SummaryPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/ApplicantPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CorrespondentPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/WhoAreYouPF.feature >> cypress/e2e/StitchedClonePFLpa.feature
# for PF clone, we stitch in the HW repeat application scenario so that this is not a repeat application
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/RepeatApplicationHW.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/FeeReductionPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/e2e/CheckoutPFClone.feature >> cypress/e2e/StitchedClonePFLpa.feature
