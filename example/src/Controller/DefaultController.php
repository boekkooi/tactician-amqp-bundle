<?php
namespace Example\Controller;

use Example\Command\DirectCommand;
use Example\Command\RpcCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');

            /** @var \League\Tactician\CommandBus $commandBus */
            $commandBus = $this->get('tactician.commandbus');

            if ($request->request->has('direct')) {
                $result = $commandBus->handle(new DirectCommand($name));
            } elseif ($request->request->has('rpc')) {
                $result = $commandBus->handle(new RpcCommand($name));
            }
        }

        return $this->render('ExampleBundle::index.html.php', ['result' => $result]);
    }
}
