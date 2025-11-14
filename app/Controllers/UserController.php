<?php

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Response;
use App\Models\User;

/**
 * User Controller
 *
 * Example controller demonstrating CRUD operations with the framework.
 */
class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @return Response
     */
    public function index(): Response
    {
        $users = User::all();

        return $this->view('users.index', [
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new user
     *
     * @return Response
     */
    public function create(): Response
    {
        return $this->view('users.create');
    }

    /**
     * Store a newly created user in database
     *
     * @return Response
     */
    public function store(): Response
    {
        // Validate the request
        $validated = $this->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        // Create the user
        $user = User::createUser($validated);

        // Flash success message
        $this->flash('success', 'User created successfully!');

        // Redirect to user detail
        return $this->redirect("/users/{$user->id}");
    }

    /**
     * Display the specified user
     *
     * @param string $id
     * @return Response
     */
    public function show(string $id): Response
    {
        try {
            $user = User::findOrFail($id);

            return $this->view('users.show', [
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return Response::notFound('User not found');
        }
    }

    /**
     * Show the form for editing a user
     *
     * @param string $id
     * @return Response
     */
    public function edit(string $id): Response
    {
        try {
            $user = User::findOrFail($id);

            return $this->view('users.edit', [
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return Response::notFound('User not found');
        }
    }

    /**
     * Update the specified user in database
     *
     * @param string $id
     * @return Response
     */
    public function update(string $id): Response
    {
        try {
            $user = User::findOrFail($id);

            // Validate the request
            $validated = $this->validate([
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|email|max:255',
            ]);

            // Update the user
            $user->fill($validated);
            $user->save();

            // Flash success message
            $this->flash('success', 'User updated successfully!');

            // Redirect to user detail
            return $this->redirect("/users/{$user->id}");
        } catch (\Exception $e) {
            return Response::notFound('User not found');
        }
    }

    /**
     * Remove the specified user from database
     *
     * @param string $id
     * @return Response
     */
    public function destroy(string $id): Response
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            // Flash success message
            $this->flash('success', 'User deleted successfully!');

            // Redirect to users list
            return $this->redirect('/users');
        } catch (\Exception $e) {
            return Response::notFound('User not found');
        }
    }

    /**
     * API endpoint - Return users as JSON
     *
     * @return Response
     */
    public function apiIndex(): Response
    {
        $users = User::all();

        return $this->json([
            'success' => true,
            'data' => array_map(fn($user) => $user->toArray(), $users)
        ]);
    }

    /**
     * API endpoint - Return a single user as JSON
     *
     * @param string $id
     * @return Response
     */
    public function apiShow(string $id): Response
    {
        try {
            $user = User::findOrFail($id);

            return $this->json([
                'success' => true,
                'data' => $user->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}
