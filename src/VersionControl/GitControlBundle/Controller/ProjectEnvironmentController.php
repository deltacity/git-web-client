<?php
/*
 * This file is part of the GitControlBundle package.
 *
 * (c) Paul Schweppe <paulschweppe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VersionControl\GitControlBundle\Controller;

use VersionControl\GitControlBundle\Controller\Base\BaseProjectController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionControl\GitControlBundle\Entity\Project;
use VersionControl\GitControlBundle\Entity\ProjectEnvironment;
use VersionControl\GitControlBundle\Form\ProjectEnvironmentType;
use VersionControl\GitControlBundle\Annotation\ProjectAccess;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Project controller.
 *
 * @Route("/project/{id}/project-environment")
 */
class ProjectEnvironmentController extends BaseProjectController
{
    /**
     * Creates a new Project entity.
     *
     * @Route("/{gitAction}", name="projectenvironment_create")
     * @Method("POST")
     * @Template("VersionControlGitControlBundle:ProjectEnvironment:new.html.twig")
     * @ProjectAccess(grantType="OWNER")
     */
    public function createAction(Request $request, $gitAction = '')
    {
        $projectEnvironment = new ProjectEnvironment();
        $form = $this->createCreateForm($projectEnvironment, $this->project, $gitAction);
        //$form   = $this->createCreateForm($projectEnvironment,$project,'clone');
        $form->handleRequest($request);

        if ($form->isValid()) {
            $gitAction = $form->get('gitaction')->getData();

            $em = $this->getDoctrine()->getManager();

            //Set Project
            $projectEnvironment->setProject($this->project);

            if ($gitAction === 'new') {
                //Create git repo
                $this->createEmptyGitRepository($projectEnvironment);
            } elseif ($gitAction === 'clone') {
                //Create Git Clone

                $this->cloneGitRepository($projectEnvironment);
            }

            $em->persist($projectEnvironment);
            $em->flush();

            return $this->redirect($this->generateUrl('project_edit'));
        }

        return  array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Project entity.
     *
     * @param Project $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ProjectEnvironment $entity, $project, $gitaction = '')
    {
        if ($gitaction == 'clone') {
            $projectEnvironmentType = new ProjectEnvironmentType(true);
        } else {
            $projectEnvironmentType = new ProjectEnvironmentType(false);
        }
        $form = $this->createForm($projectEnvironmentType, $entity, array(
            'action' => $this->generateUrl('projectenvironment_create', array('gitAction' => $gitaction)),
            'method' => 'POST',
        ));

        $form->add('gitaction', 'hidden', array(
            'mapped' => false,
            'empty_data' => false,
            'required' => 'required',
            'data' => $gitaction,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Project entity.
     *
     * @Route("/new/", name="projectenvironment_new")
     * @Method("GET")
     * @Template("VersionControlGitControlBundle:ProjectEnvironment:new.html.twig")
     * @ProjectAccess(grantType="OWNER")
     */
    public function newAction()
    {
        $projectEnvironment = new ProjectEnvironment();
        $form = $this->createCreateForm($projectEnvironment, $this->project, 'new');

        return  array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Project entity.
     *
     * @Route("/clone/", name="projectenvironment_clone")
     * @Method("GET")
     * @Template("VersionControlGitControlBundle:ProjectEnvironment:new.html.twig")
     * @ProjectAccess(grantType="OWNER")
     */
    public function cloneAction()
    {
        $projectEnvironment = new ProjectEnvironment();
        $form = $this->createCreateForm($projectEnvironment, $this->project, 'clone');

        return  array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Project entity.
     *
     * @Route("/existing/", name="projectenvironment_existing")
     * @Method("GET")
     * @Template("VersionControlGitControlBundle:ProjectEnvironment:new.html.twig")
     * @ProjectAccess(grantType="OWNER")
     */
    public function existingAction()
    {
        $projectEnvironment = new ProjectEnvironment();
        $form = $this->createCreateForm($projectEnvironment, $this->project);

        return array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Project entity.
     *
     * @Route("/edit/{projectEnvironmentId}", name="projectenvironment_edit")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="OWNER")
     */
    public function editAction($projectEnvironmentId)
    {
        $em = $this->getDoctrine()->getManager();

        $projectEnvironment = $em->getRepository('VersionControlGitControlBundle:ProjectEnvironment')->find($projectEnvironmentId);

        if (!$projectEnvironment) {
            throw $this->createNotFoundException('Unable to find Project Environment entity.');
        }

        $editForm = $this->createEditForm($projectEnvironment);
        $deleteForm = $this->createDeleteForm($projectEnvironmentId);

        return array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Project entity.
     *
     * @param Project $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(ProjectEnvironment $entity)
    {
        $form = $this->createForm(ProjectEnvironmentType::class, $entity, array(
            'action' => $this->generateUrl('projectenvironment_update', array('projectEnvironmentId' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Project entity.
     *
     * @Route("/{projectEnvironmentId}", name="projectenvironment_update")
     * @Method("PUT")
     * @Template("VersionControlGitControlBundle:ProjectEnvironment:edit.html.twig")
     * @ProjectAccess(grantType="OWNER")
     */
    public function updateAction(Request $request, $projectEnvironmentId)
    {
        $em = $this->getDoctrine()->getManager();

        $projectEnvironment = $em->getRepository('VersionControlGitControlBundle:ProjectEnvironment')->find($projectEnvironmentId);

        if (!$projectEnvironment) {
            throw $this->createNotFoundException('Unable to find Project Environment entity.');
        }

        $deleteForm = $this->createDeleteForm($projectEnvironmentId);
        $editForm = $this->createEditForm($projectEnvironment);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Project Environment record updated');

            return $this->redirect($this->generateUrl('projectenvironment_edit', array('projectEnvironmentId' => $projectEnvironmentId)));
        }

        return array_merge($this->viewVariables, array(
            'projectEnvironment' => $projectEnvironment,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Project entity.
     *
     * @Route("/", name="projectenvironment_delete")
     * @Method("DELETE")
     * @ProjectAccess(grantType="OWNER")
     */
    public function deleteAction(Request $request, $projectEnvironmentId)
    {
        $em = $this->getDoctrine()->getManager();
        $projectEnvironment = $em->getRepository('VersionControlGitControlBundle:ProjectEnvironment')->find($projectEnvironmentId);

        if (!$projectEnvironment) {
            throw $this->createNotFoundException('Unable to find Project Environment entity.');
        }

        $form = $this->createDeleteForm($projectEnvironmentId);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->remove($projectEnvironment);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('project_edit'));
    }

    /**
     * Creates a form to delete a Project entity by id.
     *
     * @param mixed $projectEnvironmentId The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($projectEnvironmentId)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('projectenvironment_delete', array('projectEnvironmentId' => $projectEnvironmentId)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    protected function createEmptyGitRepository($projectEnvironment)
    {
        $gitCommands = $this->gitCommands->command('init')->overRideGitEnvironment($projectEnvironment);

        $response = $gitCommands->initRepository();

        $this->get('session')->getFlashBag()->add('notice', $response);
    }

    protected function cloneGitRepository($projectEnvironment)
    {
        //$projectEnvironment->getGitCloneLocation();
        $gitCommands = $this->gitCommands->command('init')->overRideGitEnvironment($projectEnvironment);
        try {
            $response = $gitCommands->cloneRepository($projectEnvironment->getGitCloneLocation());
            $this->get('session')->getFlashBag()->add('notice', $response);
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
    }

    /**
     * @param int $id
     */
    public function initAction($id, $grantType = 'VIEW')
    {
        $em = $this->getDoctrine()->getManager();

        $this->project = $em->getRepository('VersionControlGitControlBundle:Project')->find($id);

        if (!$this->project) {
            throw $this->createNotFoundException('Unable to find Project entity.');
        }
        $this->checkProjectAuthorization($this->project, $grantType);

        $projectEnvironment = $this->getProjectEnvironment();

        if ($projectEnvironment) {
            $this->gitCommands = $this->get('version_control.git_commands')->setGitEnvironment($projectEnvironment);
        } else {
            $this->gitCommands = $this->get('version_control.git_commands');
        }
        $this->gitCommands = $this->get('version_control.git_commands');
        //$this->gitBranchCommands = $this->get('version_control.git_branch')->setProject($this->project);

        //$this->branchName = $this->gitCommands->command('branch')->getCurrentBranch();
        $this->viewVariables = array_merge($this->viewVariables, array(
            'project' => $this->project,
            'branchName' => $this->branchName,
            ));
    }

    /**
     * Gets the project Environment for the Project Environment Storage.
     */
    public function getProjectEnvironment()
    {
        $projectEnvironmentStorage = $this->get('version_control.project_environmnent_storage');

        return $projectEnvironmentStorage->getProjectEnviromment($this->project);
    }
}
