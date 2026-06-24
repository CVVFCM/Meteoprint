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

{{/*
Container env for the bundled DB: pull the Bitnami-generated password from its Secret
and compose DATABASE_URL with k8s $(VAR) expansion. PGPASSWORD must precede DATABASE_URL
so the runtime substitution resolves. Empty unless postgresql.enabled.
*/}}
{{- define "meteoprint.dbEnv" -}}
{{- if .Values.postgresql.enabled }}
- name: PGPASSWORD
  valueFrom:
    secretKeyRef:
      name: {{ .Values.postgresql.auth.existingSecret | default (printf "%s-postgresql" .Release.Name) }}
      key: {{ .Values.postgresql.auth.secretKeys.userPasswordKey | default "password" }}
- name: DATABASE_URL
  value: {{ printf "postgresql://%s:$(PGPASSWORD)@%s-postgresql:5432/%s?serverVersion=%s&charset=utf8" .Values.postgresql.auth.username .Release.Name .Values.postgresql.auth.database .Values.externalDatabase.serverVersion | quote }}
{{- end }}
{{- end }}
