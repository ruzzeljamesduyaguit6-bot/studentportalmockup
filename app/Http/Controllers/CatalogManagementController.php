<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogManagementController extends Controller
{
    private array $catalogConfig = [
        'designations' => [
            'table' => 'designations',
            'label' => 'Designation',
        ],
        'departments' => [
            'table' => 'departments',
            'label' => 'Department',
        ],
        'courses' => [
            'table' => 'courses',
            'label' => 'Course',
        ],
        'subjects' => [
            'table' => 'subjects',
            'label' => 'Subject',
        ],
    ];

    private array $degreeLevels = [
        ['value' => 'associate', 'label' => 'Associate Degree'],
        ['value' => 'bachelor', 'label' => 'Bachelor\'s Degree'],
        ['value' => 'master', 'label' => 'Master\'s Degree'],
        ['value' => 'doctorate', 'label' => 'Doctorate Degree'],
        ['value' => 'certificate', 'label' => 'Certificate Program'],
        ['value' => 'diploma', 'label' => 'Diploma Program'],
    ];

    private function authenticateAdmin(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $hashedToken = hash('sha256', $token);
        $user = User::where('api_token', $hashedToken)->first();

        if (!$user || $user->user_type !== 'admin') {
            return null;
        }

        return $user;
    }

    private function resolveCatalog(string $type): ?array
    {
        return $this->catalogConfig[$type] ?? null;
    }

    private function normalizeIdArray(array $values): array
    {
        return array_values(array_unique(array_map('intval', $values)));
    }

    private function invalidSubjectsForDepartment(array $subjectIds, ?int $departmentId): array
    {
        if (empty($subjectIds)) {
            return [];
        }

        $subjects = DB::table('subjects')
            ->whereIn('id', $subjectIds)
            ->get(['id', 'name', 'department_id', 'is_free_for_all']);

        return $subjects
            ->filter(function ($subject) use ($departmentId) {
                if ((bool) $subject->is_free_for_all) {
                    return false;
                }

                if (!$departmentId) {
                    return true;
                }

                return (int) $subject->department_id !== (int) $departmentId;
            })
            ->pluck('name')
            ->values()
            ->toArray();
    }

    private function invalidCoursesForDepartment(array $courseIds, ?int $departmentId): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $courses = DB::table('courses')
            ->whereIn('id', $courseIds)
            ->get(['id', 'name', 'department_id']);

        return $courses
            ->filter(function ($course) use ($departmentId) {
                if (!$departmentId) {
                    return true;
                }

                return (int) $course->department_id !== (int) $departmentId;
            })
            ->pluck('name')
            ->values()
            ->toArray();
    }

    private function pruneIncompatibleCourseSubjects(array $courseIds): void
    {
        $normalizedCourseIds = $this->normalizeIdArray($courseIds);

        foreach ($normalizedCourseIds as $courseId) {
            $course = DB::table('courses')->where('id', $courseId)->first(['id', 'department_id']);
            if (!$course) {
                continue;
            }

            $incompatibleQuery = DB::table('course_subject')
                ->join('subjects', 'subjects.id', '=', 'course_subject.subject_id')
                ->where('course_subject.course_id', $courseId)
                ->where('subjects.is_free_for_all', false);

            if ($course->department_id) {
                $incompatibleQuery->where(function ($query) use ($course) {
                    $query->where('subjects.department_id', '!=', $course->department_id)
                        ->orWhereNull('subjects.department_id');
                });
            }

            $incompatibleSubjectIds = $incompatibleQuery
                ->pluck('course_subject.subject_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->toArray();

            if (!empty($incompatibleSubjectIds)) {
                DB::table('course_subject')
                    ->where('course_id', $courseId)
                    ->whereIn('subject_id', $incompatibleSubjectIds)
                    ->delete();
            }
        }
    }

    public function subjectCourses(Request $request)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $departmentId = (int) $request->query('department_id', 0);

        $courses = DB::table('courses')
            ->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
            ->when($departmentId > 0, function ($query) use ($departmentId) {
                $query->where('courses.department_id', $departmentId);
            })
            ->orderBy('courses.name')
            ->get([
                'courses.id',
                'courses.name',
                'courses.course_code',
                'courses.degree_level',
                'courses.total_years',
                'courses.department_id',
                DB::raw('departments.name as department_name'),
            ]);

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    public function courseSubjects(Request $request)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $departmentId = (int) $request->query('department_id', 0);

        $subjects = DB::table('subjects')
            ->leftJoin('departments', 'subjects.department_id', '=', 'departments.id')
            ->when($departmentId > 0, function ($query) use ($departmentId) {
                $query->where(function ($innerQuery) use ($departmentId) {
                    $innerQuery->where('subjects.is_free_for_all', true)
                        ->orWhere('subjects.department_id', $departmentId);
                });
            })
            ->orderBy('subjects.name')
            ->get([
                'subjects.id',
                'subjects.name',
                'subjects.subject_code',
                'subjects.units',
                'subjects.department_id',
                'subjects.is_free_for_all',
                DB::raw('departments.name as department_name'),
            ]);

        return response()->json([
            'success' => true,
            'subjects' => $subjects,
        ]);
    }

    public function getCourseSubjectAssignments(Request $request, int $courseId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $course = DB::table('courses')->where('id', $courseId)->first([
            'id',
            'name',
            'course_code',
            'degree_level',
            'total_years',
            'department_id',
        ]);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $subjectIds = DB::table('course_subject')
            ->where('course_id', $courseId)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'success' => true,
            'course' => $course,
            'course_id' => $courseId,
            'subject_ids' => $subjectIds,
        ]);
    }

    public function getSubjectCourseAssignments(Request $request, int $subjectId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $subject = DB::table('subjects')->where('id', $subjectId)->first([
            'id',
            'name',
            'subject_code',
            'units',
            'department_id',
            'is_free_for_all',
        ]);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $courseIds = DB::table('course_subject')
            ->where('subject_id', $subjectId)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'subject_id' => $subjectId,
            'course_ids' => $courseIds,
        ]);
    }

    public function getDepartmentCourseAssignments(Request $request, int $departmentId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $department = DB::table('departments')->where('id', $departmentId)->first(['id', 'name']);
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $courseIds = DB::table('courses')
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'success' => true,
            'department' => $department,
            'course_ids' => $courseIds,
        ]);
    }

    public function updateDepartmentCourses(Request $request, int $departmentId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $department = DB::table('departments')->where('id', $departmentId)->first(['id']);
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:departments,name,' . $departmentId,
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
        ]);

        $courseIds = $this->normalizeIdArray($validated['course_ids'] ?? []);
        $previousCourseIds = DB::table('courses')
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        DB::transaction(function () use ($departmentId, $validated, $courseIds) {
            if (array_key_exists('name', $validated) && trim((string) $validated['name']) !== '') {
                DB::table('departments')
                    ->where('id', $departmentId)
                    ->update([
                        'name' => trim((string) $validated['name']),
                        'updated_at' => now(),
                    ]);
            }

            if (empty($courseIds)) {
                DB::table('courses')
                    ->where('department_id', $departmentId)
                    ->update([
                        'department_id' => null,
                        'updated_at' => now(),
                    ]);
                return;
            }

            DB::table('courses')
                ->where('department_id', $departmentId)
                ->whereNotIn('id', $courseIds)
                ->update([
                    'department_id' => null,
                    'updated_at' => now(),
                ]);

            DB::table('courses')
                ->whereIn('id', $courseIds)
                ->update([
                    'department_id' => $departmentId,
                    'updated_at' => now(),
                ]);
        });

        $affectedCourseIds = array_values(array_unique(array_merge($previousCourseIds, $courseIds)));
        if (!empty($affectedCourseIds)) {
            $this->pruneIncompatibleCourseSubjects($affectedCourseIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Department courses updated successfully',
        ]);
    }

    public function updateCourseSubjects(Request $request, int $courseId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $course = DB::table('courses')->where('id', $courseId)->first(['id', 'department_id']);
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $validated = $request->validate([
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $subjectIds = $this->normalizeIdArray($validated['subject_ids'] ?? []);
        $invalidSubjects = $this->invalidSubjectsForDepartment($subjectIds, $course->department_id ? (int) $course->department_id : null);

        if (!empty($invalidSubjects)) {
            return response()->json([
                'success' => false,
                'message' => 'Some subjects do not belong to this course department.',
                'invalid_subjects' => $invalidSubjects,
            ], 422);
        }

        DB::transaction(function () use ($courseId, $subjectIds) {
            DB::table('course_subject')->where('course_id', $courseId)->delete();

            if (!empty($subjectIds)) {
                $rows = array_map(function ($subjectId) use ($courseId) {
                    return [
                        'course_id' => $courseId,
                        'subject_id' => $subjectId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $subjectIds);

                DB::table('course_subject')->insert($rows);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Course subjects updated successfully',
        ]);
    }

    public function updateSubjectCourses(Request $request, int $subjectId)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $subject = DB::table('subjects')->where('id', $subjectId)->first([
            'id',
            'department_id',
            'is_free_for_all',
            'name',
            'subject_code',
            'units',
        ]);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:subjects,name,' . $subjectId,
            'subject_code' => 'sometimes|nullable|string|max:40|unique:subjects,subject_code,' . $subjectId,
            'units' => 'sometimes|required|integer|min:1|max:12',
            'department_id' => 'nullable|integer|exists:departments,id',
            'is_free_for_all' => 'nullable|boolean',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
        ]);

        $courseIds = $this->normalizeIdArray($validated['course_ids'] ?? []);

        $isFreeForAll = array_key_exists('is_free_for_all', $validated)
            ? (bool) $validated['is_free_for_all']
            : (bool) $subject->is_free_for_all;

        $departmentId = array_key_exists('department_id', $validated)
            ? ($validated['department_id'] ? (int) $validated['department_id'] : null)
            : ($subject->department_id ? (int) $subject->department_id : null);

        if ($isFreeForAll) {
            $departmentId = null;
        }

        if (!$isFreeForAll && !$departmentId) {
            return response()->json([
                'success' => false,
                'message' => 'Subject department is required when Free for All is disabled.',
            ], 422);
        }

        if (!$isFreeForAll) {
            $invalidCourses = $this->invalidCoursesForDepartment($courseIds, $departmentId);
            if (!empty($invalidCourses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected courses do not belong to this subject department.',
                    'invalid_courses' => $invalidCourses,
                ], 422);
            }
        }

        $subjectPayload = [
            'department_id' => $departmentId,
            'is_free_for_all' => $isFreeForAll,
            'updated_at' => now(),
        ];

        if (array_key_exists('name', $validated)) {
            $subjectPayload['name'] = trim((string) $validated['name']);
        }

        if (array_key_exists('subject_code', $validated)) {
            $subjectPayload['subject_code'] = strtoupper(trim((string) $validated['subject_code']));
        }

        if (array_key_exists('units', $validated)) {
            $subjectPayload['units'] = (int) $validated['units'];
        }

        DB::transaction(function () use ($subjectId, $subjectPayload, $courseIds) {
            DB::table('subjects')->where('id', $subjectId)->update($subjectPayload);

            DB::table('course_subject')->where('subject_id', $subjectId)->delete();

            if (!empty($courseIds)) {
                $rows = array_map(function ($courseId) use ($subjectId) {
                    return [
                        'subject_id' => $subjectId,
                        'course_id' => $courseId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $courseIds);

                DB::table('course_subject')->insert($rows);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
        ]);
    }

    public function options(Request $request)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $departments = DB::table('departments')->orderBy('name')->get(['id', 'name']);

        $courses = DB::table('courses')
            ->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
            ->orderBy('courses.name')
            ->get([
                'courses.id',
                'courses.name',
                'courses.course_code',
                'courses.degree_level',
                'courses.total_years',
                'courses.department_id',
                DB::raw('departments.name as department_name'),
            ]);

        $subjects = DB::table('subjects')
            ->leftJoin('departments', 'subjects.department_id', '=', 'departments.id')
            ->orderBy('subjects.name')
            ->get([
                'subjects.id',
                'subjects.name',
                'subjects.subject_code',
                'subjects.units',
                'subjects.department_id',
                'subjects.is_free_for_all',
                DB::raw('departments.name as department_name'),
            ]);

        return response()->json([
            'success' => true,
            'designations' => DB::table('designations')->orderBy('name')->pluck('name')->toArray(),
            'departments' => $departments->pluck('name')->toArray(),
            'courses' => $courses->pluck('name')->toArray(),
            'subjects' => $subjects->pluck('name')->toArray(),
            'department_options' => $departments,
            'course_options' => $courses,
            'subject_options' => $subjects,
            'degree_levels' => $this->degreeLevels,
        ]);
    }

    public function index(Request $request, string $type)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $catalog = $this->resolveCatalog($type);
        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid catalog type'
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('perPage', 15)));
        $search = trim((string) $request->query('search', ''));
        $departmentId = max(0, (int) $request->query('department_id', 0));

        $total = DB::table($catalog['table'])->count();

        if ($type === 'courses') {
            $filteredQuery = DB::table('courses')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('courses.name', 'like', '%' . $search . '%')
                            ->orWhere('courses.course_code', 'like', '%' . $search . '%');
                    });
                })
                ->when($departmentId > 0, function ($query) use ($departmentId) {
                    $query->where('courses.department_id', $departmentId);
                });

            $filteredTotal = (clone $filteredQuery)->count();
            $totalPages = max(1, (int) ceil($filteredTotal / $perPage));
            $page = min($page, $totalPages);

            $items = DB::table('courses')
                ->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
                ->leftJoin('course_subject', 'courses.id', '=', 'course_subject.course_id')
                ->leftJoin('subjects', 'subjects.id', '=', 'course_subject.subject_id')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('courses.name', 'like', '%' . $search . '%')
                            ->orWhere('courses.course_code', 'like', '%' . $search . '%');
                    });
                })
                ->when($departmentId > 0, function ($query) use ($departmentId) {
                    $query->where('courses.department_id', $departmentId);
                })
                ->groupBy(
                    'courses.id',
                    'courses.name',
                    'courses.course_code',
                    'courses.degree_level',
                    'courses.total_years',
                    'courses.department_id',
                    'courses.created_at',
                    'departments.name'
                )
                ->orderBy('courses.name')
                ->forPage($page, $perPage)
                ->get([
                    'courses.id',
                    'courses.name',
                    'courses.course_code',
                    'courses.degree_level',
                    'courses.total_years',
                    'courses.department_id',
                    'courses.created_at',
                    DB::raw('departments.name as department_name'),
                    DB::raw('COUNT(DISTINCT course_subject.subject_id) as subjects_count'),
                    DB::raw('COALESCE(SUM(subjects.units), 0) as total_units'),
                ])
                ->map(function ($item) {
                    $item->subjects_count = (int) $item->subjects_count;
                    $item->total_units = (int) $item->total_units;
                    $item->total_years = $item->total_years ? (int) $item->total_years : null;
                    return $item;
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'type' => $type,
                'label' => $catalog['label'],
                'items' => $items,
                'total' => $total,
                'filteredTotal' => $filteredTotal,
                'search' => $search,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'filters' => [
                    'department_id' => $departmentId,
                ],
                'departmentOptions' => DB::table('departments')->orderBy('name')->get(['id', 'name']),
            ]);
        }

        if ($type === 'subjects') {
            $filteredQuery = DB::table('subjects')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('subjects.name', 'like', '%' . $search . '%')
                            ->orWhere('subjects.subject_code', 'like', '%' . $search . '%');
                    });
                })
                ->when($departmentId > 0, function ($query) use ($departmentId) {
                    $query->where(function ($inner) use ($departmentId) {
                        $inner->where('subjects.department_id', $departmentId)
                            ->orWhere('subjects.is_free_for_all', true);
                    });
                });

            $filteredTotal = (clone $filteredQuery)->count();
            $totalPages = max(1, (int) ceil($filteredTotal / $perPage));
            $page = min($page, $totalPages);

            $items = DB::table('subjects')
                ->leftJoin('departments', 'subjects.department_id', '=', 'departments.id')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('subjects.name', 'like', '%' . $search . '%')
                            ->orWhere('subjects.subject_code', 'like', '%' . $search . '%');
                    });
                })
                ->when($departmentId > 0, function ($query) use ($departmentId) {
                    $query->where(function ($inner) use ($departmentId) {
                        $inner->where('subjects.department_id', $departmentId)
                            ->orWhere('subjects.is_free_for_all', true);
                    });
                })
                ->orderBy('subjects.name')
                ->forPage($page, $perPage)
                ->get([
                    'subjects.id',
                    'subjects.name',
                    'subjects.subject_code',
                    'subjects.units',
                    'subjects.department_id',
                    'subjects.is_free_for_all',
                    'subjects.created_at',
                    DB::raw('departments.name as department_name'),
                ])
                ->map(function ($item) {
                    $item->units = $item->units ? (int) $item->units : null;
                    $item->is_free_for_all = (bool) $item->is_free_for_all;
                    return $item;
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'type' => $type,
                'label' => $catalog['label'],
                'items' => $items,
                'total' => $total,
                'filteredTotal' => $filteredTotal,
                'search' => $search,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'filters' => [
                    'department_id' => $departmentId,
                ],
                'departmentOptions' => DB::table('departments')->orderBy('name')->get(['id', 'name']),
            ]);
        }

        if ($type === 'departments') {
            $filteredQuery = DB::table('departments')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });

            $filteredTotal = (clone $filteredQuery)->count();
            $totalPages = max(1, (int) ceil($filteredTotal / $perPage));
            $page = min($page, $totalPages);

            $items = DB::table('departments')
                ->leftJoin('courses', 'departments.id', '=', 'courses.department_id')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('departments.name', 'like', '%' . $search . '%');
                })
                ->groupBy('departments.id', 'departments.name', 'departments.created_at')
                ->orderBy('departments.name')
                ->forPage($page, $perPage)
                ->get([
                    'departments.id',
                    'departments.name',
                    'departments.created_at',
                    DB::raw('COUNT(courses.id) as courses_count'),
                ])
                ->map(function ($item) {
                    $item->courses_count = (int) $item->courses_count;
                    return $item;
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'type' => $type,
                'label' => $catalog['label'],
                'items' => $items,
                'total' => $total,
                'filteredTotal' => $filteredTotal,
                'search' => $search,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ]);
        }

        $query = DB::table($catalog['table']);
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $filteredTotal = (clone $query)->count();
        $totalPages = max(1, (int) ceil($filteredTotal / $perPage));
        $page = min($page, $totalPages);

        $items = $query
            ->orderBy('name')
            ->forPage($page, $perPage)
            ->get(['id', 'name', 'created_at'])
            ->toArray();

        return response()->json([
            'success' => true,
            'type' => $type,
            'label' => $catalog['label'],
            'items' => $items,
            'total' => $total,
            'filteredTotal' => $filteredTotal,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $catalog = $this->resolveCatalog($type);
        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid catalog type'
            ], 404);
        }

        if ($type === 'departments') {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name',
                'course_ids' => 'nullable|array',
                'course_ids.*' => 'integer|exists:courses,id',
            ]);

            $departmentId = DB::table('departments')->insertGetId([
                'name' => trim($validated['name']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $courseIds = $this->normalizeIdArray($validated['course_ids'] ?? []);
            if (!empty($courseIds)) {
                DB::table('courses')
                    ->whereIn('id', $courseIds)
                    ->update([
                        'department_id' => $departmentId,
                        'updated_at' => now(),
                    ]);

                $this->pruneIncompatibleCourseSubjects($courseIds);
            }

            return response()->json([
                'success' => true,
                'message' => $catalog['label'] . ' created successfully',
                'item' => [
                    'id' => $departmentId,
                    'name' => trim($validated['name']),
                ],
            ], 201);
        }

        if ($type === 'courses') {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:courses,name',
                'course_code' => 'required|string|max:40|unique:courses,course_code',
                'degree_level' => 'required|in:associate,bachelor,master,doctorate,certificate,diploma',
                'total_years' => 'required|integer|min:1|max:10',
                'department_id' => 'required|integer|exists:departments,id',
                'subject_ids' => 'nullable|array',
                'subject_ids.*' => 'integer|exists:subjects,id',
            ]);

            $departmentId = (int) $validated['department_id'];
            $subjectIds = $this->normalizeIdArray($validated['subject_ids'] ?? []);
            $invalidSubjects = $this->invalidSubjectsForDepartment($subjectIds, $departmentId);

            if (!empty($invalidSubjects)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some subjects do not belong to the selected department.',
                    'invalid_subjects' => $invalidSubjects,
                ], 422);
            }

            $courseId = DB::table('courses')->insertGetId([
                'name' => trim((string) $validated['name']),
                'course_code' => strtoupper(trim((string) $validated['course_code'])),
                'degree_level' => $validated['degree_level'],
                'total_years' => (int) $validated['total_years'],
                'department_id' => $departmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($subjectIds)) {
                $rows = array_map(function ($subjectId) use ($courseId) {
                    return [
                        'course_id' => $courseId,
                        'subject_id' => $subjectId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $subjectIds);

                DB::table('course_subject')->insert($rows);
            }

            return response()->json([
                'success' => true,
                'message' => $catalog['label'] . ' created successfully',
                'item' => [
                    'id' => $courseId,
                    'name' => trim((string) $validated['name']),
                    'course_code' => strtoupper(trim((string) $validated['course_code'])),
                    'degree_level' => $validated['degree_level'],
                    'total_years' => (int) $validated['total_years'],
                    'department_id' => $departmentId,
                ],
            ], 201);
        }

        if ($type === 'subjects') {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:subjects,name',
                'subject_code' => 'required|string|max:40|unique:subjects,subject_code',
                'units' => 'required|integer|min:1|max:12',
                'department_id' => 'nullable|integer|exists:departments,id',
                'is_free_for_all' => 'nullable|boolean',
                'course_ids' => 'nullable|array',
                'course_ids.*' => 'integer|exists:courses,id',
            ]);

            $isFreeForAll = (bool) ($validated['is_free_for_all'] ?? false);
            $departmentId = $isFreeForAll
                ? null
                : ($validated['department_id'] ? (int) $validated['department_id'] : null);

            if (!$isFreeForAll && !$departmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject department is required when Free for All is disabled.',
                ], 422);
            }

            $courseIds = $this->normalizeIdArray($validated['course_ids'] ?? []);

            if (!$isFreeForAll) {
                $invalidCourses = $this->invalidCoursesForDepartment($courseIds, $departmentId);
                if (!empty($invalidCourses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Some selected courses do not belong to the selected department.',
                        'invalid_courses' => $invalidCourses,
                    ], 422);
                }
            }

            $subjectId = DB::table('subjects')->insertGetId([
                'name' => trim((string) $validated['name']),
                'subject_code' => strtoupper(trim((string) $validated['subject_code'])),
                'units' => (int) $validated['units'],
                'department_id' => $departmentId,
                'is_free_for_all' => $isFreeForAll,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($courseIds)) {
                $rows = array_map(function ($courseId) use ($subjectId) {
                    return [
                        'subject_id' => $subjectId,
                        'course_id' => $courseId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $courseIds);

                DB::table('course_subject')->insert($rows);
            }

            return response()->json([
                'success' => true,
                'message' => $catalog['label'] . ' created successfully',
                'item' => [
                    'id' => $subjectId,
                    'name' => trim((string) $validated['name']),
                    'subject_code' => strtoupper(trim((string) $validated['subject_code'])),
                    'units' => (int) $validated['units'],
                    'department_id' => $departmentId,
                    'is_free_for_all' => $isFreeForAll,
                ],
            ], 201);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:' . $catalog['table'] . ',name',
        ]);

        $id = DB::table($catalog['table'])->insertGetId([
            'name' => trim($validated['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $catalog['label'] . ' created successfully',
            'item' => [
                'id' => $id,
                'name' => trim($validated['name']),
            ],
        ], 201);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $admin = $this->authenticateAdmin($request);
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $catalog = $this->resolveCatalog($type);
        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid catalog type'
            ], 404);
        }

        $item = DB::table($catalog['table'])->where('id', $id)->first();
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => $catalog['label'] . ' not found'
            ], 404);
        }

        DB::table($catalog['table'])->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => $catalog['label'] . ' deleted successfully'
        ]);
    }
}
