<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum WeatherModel: string
{
    case BEST_MATCH = 'best_match';
    case ECMWF_IFS = 'ecmwf_ifs';
    case ECMWF_IFS025 = 'ecmwf_ifs025';
    case ECMWF_AIFS025_SINGLE = 'ecmwf_aifs025_single';
    case CMA_GRAPES_GLOBAL = 'cma_grapes_global';
    case BOM_ACCESS_GLOBAL = 'bom_access_global';
    case GFS_SEAMLESS = 'gfs_seamless';
    case GFS_GLOBAL = 'gfs_global';
    case GFS_HRRR = 'gfs_hrrr';
    case NCEP_NBM_CONUS = 'ncep_nbm_conus';
    case NCEP_NAM_CONUS = 'ncep_nam_conus';
    case GFS_GRAPHCAST025 = 'gfs_graphcast025';
    case NCEP_AIGFS025 = 'ncep_aigfs025';
    case NCEP_HGEFS025_ENSEMBLE_MEAN = 'ncep_hgefs025_ensemble_mean';
    case JMA_SEAMLESS = 'jma_seamless';
    case JMA_MSM = 'jma_msm';
    case JMA_GSM = 'jma_gsm';
    case KMA_SEAMLESS = 'kma_seamless';
    case KMA_LDPS = 'kma_ldps';
    case KMA_GDPS = 'kma_gdps';
    case ICON_SEAMLESS = 'icon_seamless';
    case ICON_GLOBAL = 'icon_global';
    case ICON_EU = 'icon_eu';
    case ICON_D2 = 'icon_d2';
    case GEM_SEAMLESS = 'gem_seamless';
    case GEM_GLOBAL = 'gem_global';
    case GEM_REGIONAL = 'gem_regional';
    case GEM_HRDPS_CONTINENTAL = 'gem_hrdps_continental';
    case GEM_HRDPS_WEST = 'gem_hrdps_west';
    case METEOFRANCE_SEAMLESS = 'meteofrance_seamless';
    case METEOFRANCE_ARPEGE_WORLD = 'meteofrance_arpege_world';
    case METEOFRANCE_ARPEGE_EUROPE = 'meteofrance_arpege_europe';
    case METEOFRANCE_AROME_FRANCE = 'meteofrance_arome_france';
    case METEOFRANCE_AROME_FRANCE_HD = 'meteofrance_arome_france_hd';
    case ITALIA_METEO_ARPAE_ICON_2I = 'italia_meteo_arpae_icon_2i';
    case METNO_SEAMLESS = 'metno_seamless';
    case METNO_NORDIC = 'metno_nordic';
    case KNMI_SEAMLESS = 'knmi_seamless';
    case KNMI_HARMONIE_AROME_EUROPE = 'knmi_harmonie_arome_europe';
    case KNMI_HARMONIE_AROME_NETHERLANDS = 'knmi_harmonie_arome_netherlands';
    case DMI_SEAMLESS = 'dmi_seamless';
    case DMI_HARMONIE_AROME_EUROPE = 'dmi_harmonie_arome_europe';
    case UKMO_SEAMLESS = 'ukmo_seamless';
    case UKMO_GLOBAL_DETERMINISTIC_10KM = 'ukmo_global_deterministic_10km';
    case UKMO_UK_DETERMINISTIC_2KM = 'ukmo_uk_deterministic_2km';
    case METEOSWISS_ICON_SEAMLESS = 'meteoswiss_icon_seamless';
    case METEOSWISS_ICON_CH1 = 'meteoswiss_icon_ch1';
    case METEOSWISS_ICON_CH2 = 'meteoswiss_icon_ch2';
    case GEOSPHERE_SEAMLESS = 'geosphere_seamless';
    case GEOSPHERE_AROME_AUSTRIA = 'geosphere_arome_austria';
}
