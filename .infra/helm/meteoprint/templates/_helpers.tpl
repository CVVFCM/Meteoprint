{{/*
Expand the name of the chart.
*/}}
{{- define "meteoprint.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a fully qualified app name.
*/}}
{{- define "meteoprint.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Chart name and version label value.
*/}}
{{- define "meteoprint.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels.
*/}}
{{- define "meteoprint.labels" -}}
helm.sh/chart: {{ include "meteoprint.chart" . }}
{{ include "meteoprint.selectorLabels" . }}
app.kubernetes.io/version: {{ .Values.image.tag | default .Chart.AppVersion | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels.
*/}}
{{- define "meteoprint.selectorLabels" -}}
app.kubernetes.io/name: {{ include "meteoprint.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Web component selector labels.
*/}}
{{- define "meteoprint.web.selectorLabels" -}}
{{ include "meteoprint.selectorLabels" . }}
app.kubernetes.io/component: web
{{- end }}

{{/*
Consumer component selector labels.
*/}}
{{- define "meteoprint.consumer.selectorLabels" -}}
{{ include "meteoprint.selectorLabels" . }}
app.kubernetes.io/component: consumer
{{- end }}
