<?php
namespace Example\Controller;

use Example\Command\DirectCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');

            /** @var \League\Tactician\CommandBus $commandBus */
            $commandBus = $this->get('tactician.commandbus');
            $commandBus->handle(new DirectCommand($name));
        }

        return $this->render('ExampleBundle::index.html.php');
    }
}
