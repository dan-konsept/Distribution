import {hasPermission} from '#/main/app/security'
import {url} from '#/main/app/api'
import {ASYNC_BUTTON} from '#/main/app/buttons'

import {trans, transChoice} from '#/main/app/intl/translation'

/**
 * Delete workspaces action.
 */
export default (workspaces, refresher) => ({
  name: 'delete',
  type: ASYNC_BUTTON,
  icon: 'fa fa-fw fa-trash-o',
  label: trans('delete', {}, 'actions'),
  displayed: 0 < workspaces.filter(w => hasPermission('delete', w) && w.code !== 'default_personal' && w.code !== 'default_workspace').length,
  dangerous: true,
  confirm: {
    title: trans('workspace_delete_confirm_title'),
    subtitle: 1 === workspaces.length ? workspaces[0].name : transChoice('count_elements', {count: workspaces.length}),
    message: trans('workspace_delete_confirm_message')
  },
  request: {
    url: url(['apiv2_workspace_delete_bulk'], {ids: workspaces.map(w => w.id)}),
    request: {
      method: 'DELETE'
    },
    success: () => refresher.delete(workspaces)
    // todo : make custom errors work (I can not dispatch actions from here)
    /*error: (data, status, dispatch) => {
      if (data.errors) {
        Object.values(data.errors).forEach(message => dispatch(
          alertActions.addAlert(
            'workspace-deletion',
            alertConstants.ALERT_STATUS_WARNING,
            actionConstants.ACTION_DELETE,
            null,
            message
          )
        ))
      }
    }*/
  },
  group: trans('management'),
  scope: ['object', 'collection']
})
