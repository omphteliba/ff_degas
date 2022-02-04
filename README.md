# Degas
## Automatische Bild-Beschreibung mit Azure Cognitive Services
Das Addon benutzt die "Description" Funktion der "Computer Vision API" die von den Azure Cognitive Services angeboten werden. 
Mehr Infos: https://westcentralus.dev.cognitive.microsoft.com/docs/services/computer-vision-v3-2/operations/56f91f2e778daf14a499f21f

### API Key und Endpoint erzeugen
- Azure Resource group und Azure AI Computer vision Resource anlegen auf https://portal.azure.com/#create/Microsoft.CognitiveServicesComputerVision
- Key 1 und Location/Region unter "Resource Management / Keys and endpoint" kopieren und in den Settings eintragen

### Die Dateien müssen folge Kriterien erfüllen:
- Unterstütze Bild Formate: JPEG, PNG, GIF, BMP.
- Dateien müssen kleiner 4MB sein.
- Die Bilder müssen größer als 50 x 50 Pixel sein.
