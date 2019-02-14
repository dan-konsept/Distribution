import {trans} from '#/main/app/intl/translation'

const VISIBILITY_ME = 'nobody';
const VISIBILITY_USERS = 'users';
const VISIBILITY_PLATFORM_USERS = 'platform_users';
const VISIBILITY_EVERYBODY = 'eveybody';

const TAB_TYPE_PORTFOLIO = 'portfolio'

const VISIBILITIES = {
  [VISIBILITY_ME]: trans('visibile_to_me', {}, 'portfolio'),
  [VISIBILITY_USERS]: trans('visible_for_some_users', {}, 'portfolio'),
  [VISIBILITY_PLATFORM_USERS]: trans('visible_for_platform_users', {}, 'portfolio'),
  [VISIBILITY_EVERYBODY]: trans('visible_for_everybody', {}, 'portfolio'),
}

export const constants = {
  VISIBILITY_ME,
  VISIBILITY_USERS,
  VISIBILITY_PLATFORM_USERS,
  VISIBILITY_EVERYBODY,
  VISIBILITIES,
  TAB_TYPE_PORTFOLIO
}