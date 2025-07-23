import { startStimulusApp } from '@symfony/stimulus-bundle';
import { Application } from '@hotwired/stimulus'
import Popover from '@stimulus-components/popover'

const app = startStimulusApp();
app.register('popover', Popover);
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
