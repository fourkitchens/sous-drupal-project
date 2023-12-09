#!/bin/bash

# Define the source and destination directories
SOURCE_DIR="./assets/scaffold/recipes"
DESTINATION_DIR="./web/recipes"

# Remove the contents of the destination directory
rm -rf "$DESTINATION_DIR"/*

# Create the destination directory
mkdir "$DESTINATION_DIR"

# Copy the contents of the source directory to the destination directory
cp -rf "$SOURCE_DIR/"* "$DESTINATION_DIR/"

echo "Recipes copied to $DESTINATION_DIR."